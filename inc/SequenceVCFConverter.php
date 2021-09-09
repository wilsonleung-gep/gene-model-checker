<?php
class SequenceVCFConverter
{
    public $vcfTextFile;
    public $originalSequenceFile;
    public $appConfig;
    public $binConfig;
    public $trashPath;
    public $tmpVcfBgZipPath;
    public $newFastaPath;

    public function __construct($vcfTextFile, $sequenceFile, $appConfig)
    {
        $this->vcfTextFile = $vcfTextFile;
        $this->originalSequenceFile = $sequenceFile;
        $this->appConfig = $appConfig;
        $this->binConfig = $appConfig["bin"];
        $this->tmpVcfBgZipPath = null;
        $this->newFastaPath = null;

        $this->trashPath = sprintf(
            "%s/%s",
            $this->appConfig["app"]["rootdir"],
            $this->appConfig["app"]["trashdir"]
        );
    }

    public function convertSequence($filePrefix)
    {
        $this->indexVcf($filePrefix);

        $this->createNewFastaFile($filePrefix);

        $this->removeIndexVcf();
    }

    protected function removeIndexVcf()
    {
        if (is_readable($this->tmpVcfBgZipPath)) {
            unlink($this->tmpVcfBgZipPath);
        }

        $tbiFile = $this->tmpVcfBgZipPath.".tbi";

        if (is_readable($tbiFile)) {
            unlink($tbiFile);
        }

        $this->tmpVcfBgZipPath = null;
    }

    protected function createNewFastaFile($filePrefix)
    {
        $newFastaPath = sprintf(
            "%s/%s",
            $this->trashPath, $filePrefix.".vcfupdated.fa"
        );

        $relabelSequenceFile = $this->relabelFastaID(
            $this->originalSequenceFile,
            $filePrefix
        );

        $vcfConsensusCmd = sprintf(
            "%s %s %s %s",
            $this->binConfig["vcfConsensus"],
            $relabelSequenceFile,
            $this->tmpVcfBgZipPath,
            $newFastaPath
        );

        try {
            Utilities::runCommand($vcfConsensusCmd);

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $expectedError = "Return value: 0\nLooks as fasta file snippet";
            $vcfErrorPattern = "/The fasta sequence does not match .* \.vcf/";

            if (strpos($errorMessage, $expectedError) !== 0) {
                $matches = array();

                if (preg_match($vcfErrorPattern, $errorMessage, $matches)) {
                    $revisedError = "Invalid VCF file." . $matches[0];
                    throw(new Exception($revisedError));
                }

                throw($e);
            }
        }

        $this->newFastaPath = $newFastaPath;
    }

    protected function relabelFastaID($originalFile, $filePrefix)
    {
        $seq = new DNASeq($originalFile);

        $match= array();
        if (preg_match("/(\S+):(\d+)-(\d+)$/", $seq->getHeader(), $match)) {
            $newSeqId = sprintf(
                "%s:%d-%d", $match[1], $match[2] + 1, $match[3]
            );

            $seq->setHeader($newSeqId);
        }

        $relabelSequenceFile = sprintf(
            "%s/%s",
            $this->trashPath, $filePrefix.".relabel_id.fa"
        );

        $seq->toFile($relabelSequenceFile);

        return $relabelSequenceFile;
    }

    protected function indexVcf($filePrefix)
    {
        $tmpVcfBgZipPath = sprintf(
            "%s/%s",
            $this->trashPath, $filePrefix.".gz"
        );

        $bgzipCmd = sprintf(
            "%s -c %s > %s",
            $this->binConfig["bgzip"], $this->vcfTextFile, $tmpVcfBgZipPath
        );

        Utilities::runCommand($bgzipCmd);

        $tabixCmd = sprintf(
            "%s -p vcf %s", $this->binConfig["tabix"], $tmpVcfBgZipPath
        );

        Utilities::runCommand($tabixCmd);

        $this->tmpVcfBgZipPath = $tmpVcfBgZipPath;
    }
}
