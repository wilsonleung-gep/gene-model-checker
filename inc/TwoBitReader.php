<?php
class TwoBitReader
{
    protected $appConfig;
    protected $twoBitBaseDir;

    public function __construct($appConfig)
    {
        $this->twoBitBaseDir = $appConfig["app"]["gbdbdir"];
        $this->appConfig = $appConfig;
    }

    public function extractSequence($outFilePath, $assembly, $scaffoldRange)
    {
        if (file_exists($outFilePath)) {
            return;
        }

        $twoBitPath = sprintf(
            "%s/%s/%s.2bit",
            $this->twoBitBaseDir, $assembly, $assembly
        );

        $cmd = join(
            " ",
            array(
                $this->appConfig["bin"]["twoBitToFa"],
                sprintf("-seq=%s", $scaffoldRange->getName()),
                sprintf("-start=%d", $scaffoldRange->getStartPos()),
                sprintf("-end=%d", $scaffoldRange->getEndPos()),
                "-noMask",
                $twoBitPath,
                $outFilePath
            )
        );

        exec($cmd, $output, $retcode);

        if ($retcode !== 0) {
            throw new Exception($cmd);
        }
    }
}

