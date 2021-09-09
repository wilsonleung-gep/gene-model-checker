<?php

class ProteinAligner
{
    protected $seq1FileInfo;
    protected $seq2FileInfo;
    protected $appConfig;

    const ALIGNER = '/path/to/stretcher';
    const TMP_EXT = ".txt";

    public function __construct()
    {
        $numArgs = func_num_args();

        switch($numArgs) {
        case 2:
            $iniFile = func_get_arg(0);
            $appConfig = func_get_arg(1);
            $this->constructFromIniFile($iniFile, $appConfig);
            break;
        case 3:
            list($seq1FileInfo, $seq2FileInfo, $appConfig) = func_get_args();
            $this->constructFromFileInfo($seq1FileInfo, $seq2FileInfo, $appConfig);
            break;
        default:
            throw new Exception("Invalid number of arguments");
        }
    }

    public function createAlignment($outfilePath)
    {
        $alignmentFilePath = $outfilePath.self::TMP_EXT;

        $this->alignSequences($alignmentFilePath);
        $this->colorAlignment($outfilePath);
    }

    protected function colorAlignment($outfilePath)
    {
        $cmd = join(
            " ",
            array(
                $this->appConfig["bin"]["colorAligner"],
                "-i", $outfilePath.self::TMP_EXT,
                "-o", $outfilePath,
                "-y", $this->seq1FileInfo["pepStarts"],
                "-x", $this->seq2FileInfo["pepStarts"]
            )
        );

        exec($cmd, $output, $retcode);

        if ($retcode !== 0) {
            throw new Exception("Failed to create alignment file");
        }
    }

    protected function alignSequences($outfilePath, $aformat="pair")
    {
        $cmd = join(
            " ",
            array(
                self::ALIGNER,
                "-asequence", $this->seq1FileInfo["filePath"],
                "-bsequence", $this->seq2FileInfo["filePath"],
                "-aformat", $aformat,
                "-outfile", $outfilePath
            )
        );

        exec($cmd, $output, $retcode);

        if ($retcode !== 0) {
            throw new Exception($cmd);
        }
    }

    protected function constructFromIniFile($iniFile, $appConfig)
    {
        $alignmentCfg = parse_ini_file($iniFile);

        $this->seq1FileInfo = array(
            "filePath" => $alignmentCfg["seq1Path"],
            "pepStarts" => $alignmentCfg["seq1Starts"]
        );

        $this->seq2FileInfo = array(
            "filePath" => $alignmentCfg["seq2Path"],
            "pepStarts" => $alignmentCfg["seq2Starts"]
        );

        $this->appConfig = $appConfig;
    }

    protected function constructFromFileInfo(
        $seq1FileInfo, $seq2FileInfo, $appConfig
    ) {
        $this->seq1FileInfo = $seq1FileInfo;
        $this->seq2FileInfo = $seq2FileInfo;
        $this->appConfig = $appConfig;
    }
}

?>
