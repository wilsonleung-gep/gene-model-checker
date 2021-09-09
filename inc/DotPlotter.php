<?php
class DotPlotter
{
    protected $seq1FileInfo;
    protected $seq2FileInfo;
    protected $appConfig;

    public function __construct($seq1FileInfo, $seq2FileInfo, $appConfig)
    {
        $this->seq1FileInfo = $seq1FileInfo;
        $this->seq2FileInfo = $seq2FileInfo;
        $this->appConfig = $appConfig;
    }

    public function createDotPlot($outfilePath, $wordSize=5)
    {
        $cmd = join(
            " ",
            array(
                $this->appConfig["bin"]["colorDotplot"],
                "-i", $this->seq1FileInfo["filePath"],
                "-j", $this->seq2FileInfo["filePath"],
                "-y", $this->seq1FileInfo["pepStarts"],
                "-x", $this->seq2FileInfo["pepStarts"],
                "-s", $wordSize,
                "-o", $outfilePath
            )
        );

        exec($cmd, $output, $retCode);

        if ($retCode !== 0) {
            throw new Exception($cmd);
        }
    }
}

