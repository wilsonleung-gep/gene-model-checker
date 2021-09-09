<?php
class HubPrefixFinder
{
    protected $defaultHubPrefix;
    protected $hgHubConnect;
    protected $cartDump;
    protected $hubTxt;

    function __construct($hubConfig)
    {
        $this->defaultHubPrefix = "";
        $this->hgHubConnect = $hubConfig["hgHubConnect"];
        $this->cartDump = $hubConfig["cartDump"];
        $this->hubTxt = $hubConfig["hubTxt"];
    }

    public function getHubInfo($assembly)
    {
        return array(
            "hubPrefix" => $this->defaultHubPrefix,
            "hubUrl" => $this->getHubTxt($assembly)
        );
    }

    public function getHubTxt($assembly)
    {
        if (array_key_exists($assembly, $this->hubTxt)) {
            return $this->hubTxt[$assembly];
        }

        return null;
    }

    public function findHubPrefixDynamic($assembly)
    {
        $hubConnectUrl = $this->buildHubConnectUrl($assembly);

        if ($hubConnectUrl === null) {
            return $this->defaultHubPrefix;
        }

        $curlHandle = curl_init($hubConnectUrl);
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_COOKIEJAR => true
        );

        curl_setopt_array($curlHandle, $curlOptions);

        $hubResponse = $this->requestUrl($curlHandle);

        if ($hubResponse !== null) {
            curl_setopt($curlHandle, CURLOPT_URL, $this->cartDump);

            $cartData = $this->requestUrl($curlHandle);
            $hubResponse = $this->extractHubId($cartData);
        }

        curl_close($curlHandle);

        return ($hubResponse === null) ? $this->defaultHubPrefix : $hubResponse;
    }

    protected function buildHubConnectUrl($assembly)
    {
        if (! array_key_exists($assembly, $this->hubTxt)) {
            return null;
        }

        $hubParams = array(
            "hgHub_do_redirect" => "on",
            "hgHubConnect.remakeTrackHub" => "on",
            "hgHub_do_firstDb" => 1,
            "hubClear" => $this->getHubTxt($assembly)
        );

        return sprintf(
            "%s?%s",
            $this->hgHubConnect, http_build_query($hubParams)
        );
    }

    protected function requestUrl($curlHandle)
    {
        $requestResult = curl_exec($curlHandle);

        $requestInfo = curl_getinfo($curlHandle);

        if (($requestResult === false)
            || ($requestInfo["http_code"] >= 400)
        ) {
            return null;
        }

        return $requestResult;
    }

    protected function extractHubId($cartData)
    {
        if (preg_match('/clade\s+(\S+)/', $cartData, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
