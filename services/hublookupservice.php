<?php
spl_autoload_register(
    function ($className) {
        include '../inc/' . $className . '.php';
    }
);

$APPCONFIG_FILE = "../conf/app.ini.php";
$APPCONFIG = parse_ini_file($APPCONFIG_FILE, true);

function main()
{
    global $APPCONFIG;

    $hubsConfig = $APPCONFIG["hubs"];

    $validator = untaintVariables($hubsConfig);
    $outputFormat = $validator->clean->format;

    try {
        $response = connectUcscHub($validator, $hubsConfig);

        if ($response->isSuccessful()) {
            $results = extractHubId($response);
            $results->printResult($outputFormat);

        } else {
            $response->printResult($outputFormat);
        }
    } catch (Exception $e) {
        reportErrors($e->getMessage(), $outputFormat);
    }
}

main();


function buildHubconnectUrl($validator, $hubsConfig)
{
    $hubTxt = $hubsConfig["hubTxt"][$validator->clean->assembly];

    $hubParams = array(
        "hgHub_do_redirect" => "on",
        "hgHubConnect.remakeTrackHub" => "on",
        "hgHub_do_firstDb" => 1,
        "hubClear" => $hubTxt
    );

    return sprintf(
        "%s?%s",
        $hubsConfig["hgHubConnect"], http_build_query($hubParams)
    );
}

function requestUrl($curlHandle)
{
    $requestResult = curl_exec($curlHandle);

    $requestInfo = curl_getinfo($curlHandle);

    $response = new Results();

    if ($requestResult === false) {
        $response->setStatus(false);
        $response->setMessage("Connection error: " . curl_error($curlHandle));

    } else if ($requestInfo["http_code"] >= 400) {
        $response->setStatus(false);
        $response->setMessage("Server error: " . $requestInfo["http_code"]);

    } else {
        $response->setResult($requestResult);
    }

    return $response;
}

function extractHubId($response)
{
    $results = new Results();

    $cartData = $response->getResults();

    if (preg_match('/clade\s+(\S+)/', $cartData, $matches)) {
        $results->appendResult($matches[1], "clade");
    } else {
        $results->setStatus(false);
        $results->setMessage("Unable to locate the clade field");
        return $results;
    }

    return $results;
}

function connectUcscHub($validator, $hubsConfig)
{
    $hubConnectUrl = buildHubconnectUrl($validator, $hubsConfig);

    $curlHandle = curl_init($hubConnectUrl);
    $curlOptions = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_COOKIEJAR => true
    );

    curl_setopt_array($curlHandle, $curlOptions);

    $hubResponse = requestUrl($curlHandle);

    if ($hubResponse->isSuccessful()) {
        curl_setopt($curlHandle, CURLOPT_URL, $hubsConfig["cartDump"]);
        $hubResponse = requestUrl($curlHandle);
    }

    curl_close($curlHandle);

    return $hubResponse;
}

function reportErrors($errorMessage, $outputFormat)
{
    $r = new Results(
        array(
            "status" => Results::FAILURE,
            "message" => $errorMessage
        )
    );

    echo $r->printResult($outputFormat);

    exit;
}

function untaintVariables($hubsConfig)
{
    $validator = validateVariables($hubsConfig);

    if ($validator->hasErrors()) {
        reportErrors($validator->listErrors(), $validator->clean->format);
    }

    return $validator;
}

function validateVariables($hubsConfig)
{
    $validator = new Validator($_GET);

    $validator->clean->format = "json";

    $variablesToCheck = array(
        new VType("string", "assembly", "Genome Assembly")
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    if ($validator->isValid()) {
        $assembly = $validator->clean->assembly;

        if (! array_key_exists($assembly, $hubsConfig["hubTxt"])) {
            $validator->addErrors("Unknown Genome Assembly");
        }
    }

    return $validator;
}

?>
