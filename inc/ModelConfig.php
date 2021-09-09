<?php
class ModelConfig
{
    public $appConfig;
    public $tmpFilePrefix;

    public $assembly;
    public $informant;
    public $informantGenome;
    public $scaffoldName;
    public $scaffoldLength;
    public $scaffoldRange;
    public $isoformName;

    public $codingExons;
    public $hasUTR;
    public $isReversed;
    public $isCompleteModel;

    public $partialType;
    public $transcribedExons;
    public $missFivePrimeEnd;
    public $missThreePrimeEnd;
    public $stopCodon;
    public $initialPhase;

    public $seq;
    public $seqFileName;
    public $seqLength;
    public $hasConsensusErrors;
    public $unpatchedFields;
    public $vcfTable;

    public function __construct($clean, $tmpFilePrefix, $appConfig)
    {
        $this->appConfig = $appConfig;
        $this->tmpFilePrefix = $tmpFilePrefix;

        $this->assembly = $clean->genome_db;
        $this->scaffoldName = $clean->scaffold_combo;
        $this->scaffoldLength = $clean->scaffold_length;
        $this->isoformName = $clean->ortholog_name;
        $this->informant = $appConfig["database"]["informantDb"];
        $this->informantGenome = "Dmel";

        $this->codingExons = $clean->coding_exons;
        $this->hasUTR = ($clean->annotated_utr === "yes");
        $this->isReversed = ($clean->orientation === "minus");
        $this->isCompleteModel = ($clean->model === "complete");

        $this->transcribedExons
            = ($this->hasUTR) ? $clean->transcribed_exons : $clean->coding_exons;

        if ($this->isCompleteModel) {
            $this->missFivePrimeEnd = false;
            $this->missThreePrimeEnd = false;
            $this->stopCodon = $clean->stop_codon;
            $this->initialPhase = 0;

        } else {
            $this->partialType = $clean->partial_type;

            list($this->missFivePrimeEnd, $this->missThreePrimeEnd)
                = $clean->partial_type;

            $this->initialPhase
                = (isset($clean->phase_info)) ? $clean->phase_info : 0;

            $this->stopCodon
                = (isset($clean->stop_codon)) ? $clean->stop_codon : null;
        }

       $this->createSequenceFile($clean);
    }

    protected function createSequenceFile($clean)
    {
        $this->unpatchedFields = array();

        $twoBitReader = new TwoBitReader($this->appConfig);

        $this->scaffoldRange = new ScaffoldRange(
            $this->scaffoldName, $this->scaffoldLength, $this->isReversed,
            $this->transcribedExons
        );

        $this->seqFileName = sprintf(
            "%s/%s_%s.fa",
            $this->getSeqFileDir(),
            $this->assembly,
            $this->scaffoldRange
        );

        $twoBitReader->extractSequence(
            $this->seqFileName, $this->assembly, $this->scaffoldRange
        );

        $this->hasConsensusErrors = ($clean->has_vcf === "yes");

        if ($this->hasConsensusErrors) {
           $this->patchVcfFile($clean);
        }

        $this->seq = new DNASeq($this->seqFileName);
        $this->seqLength = $this->seq->getLength();
    }

    protected function patchVcfFile($clean)
    {
        $this->vcfTable = VCFTable::loadChangesFromFile(
            $clean->vcffile["tmp_name"],
            $this->scaffoldRange
        );

        $this->unpatchedFields = array(
            "codingExons" => $this->codingExons,
            "transcribedExons" => $this->transcribedExons,
            "stopCodon" => $this->stopCodon,
            "seqFileName" => $this->seqFileName
        );

        $this->codingExons
            = $this->vcfTable->convertCoordinatesString($this->codingExons);

        $this->transcribedExons
            = $this->vcfTable->convertCoordinatesString($this->transcribedExons);

        if (isset($this->stopCodon)) {
            $this->stopCodon
                = $this->vcfTable->convertCoordinatesString($this->stopCodon);
        }

        $vcfRangeOutFilePath = $this->writeTmpVCF();

        $seqConverter = new SequenceVCFConverter(
            $vcfRangeOutFilePath, $this->seqFileName, $this->appConfig
        );

        $seqConverter->convertSequence($this->tmpFilePrefix, ".vcf");

        $this->seqFileName = $seqConverter->newFastaPath;

        $this->scaffoldRange = new ScaffoldRange(
            $this->scaffoldName,
            $this->scaffoldLength + $this->vcfTable->getTotalSizeDifference(),
            $this->isReversed,
            $this->transcribedExons
        );
    }

    protected function writeTmpVCF()
    {
        $app = $this->appConfig["app"];

        $vcfRangeOutFilePath = join(
            "/",
            array($app["rootdir"], $app["trashdir"], $this->tmpFilePrefix.".vcf")
        );

        $this->vcfTable->writeVCFFile($vcfRangeOutFilePath);

        return $vcfRangeOutFilePath;
    }

    protected function getSeqFileDir()
    {
        $seqFileDir = sprintf(
            "%s/%s/%s",
            $this->appConfig["app"]["rootdir"],
            $this->appConfig["app"]["twobitcache"],
            $this->assembly
        );

        if (! is_dir($seqFileDir)) {
            mkdir($seqFileDir, 0755);
        }

        return $seqFileDir;
    }
}
?>
