<?php
class TranscriptFeature
{
    protected $assembly;
    protected $transcriptID;
    protected $seq;
    protected $cdsCoordset;
    protected $transcriptCoordset;
    protected $cdsExons;
    protected $transcriptExons;
    protected $isReversed;
    protected $phases;
    protected $transcript;
    protected $peptide;
    protected $translatedExons;
    protected $cdsPepStarts;
    protected $scaffoldRange;

    public function __construct($seq, $modelCfg)
    {
        $this->isReversed = $modelCfg->isReversed;
        $this->transcriptID = $modelCfg->isoformName;
        $this->assembly = $modelCfg->assembly;
        $this->scaffoldRange = $modelCfg->scaffoldRange;

        $this->seq = ($this->isReversed) ? $seq->getReverseComplementSeq() : $seq;

        $this->cdsCoordset = new CoordinatesSet($modelCfg->codingExons, $modelCfg);

        $this->cdsExons
            = $this->buildExonsFromCoordset($this->seq, $this->cdsCoordset);

        $this->phases = $this->calcPhases($modelCfg->initialPhase);
        $this->cdsPepStarts = $this->calcPepStarts();

        $this->transcriptCoordset
            = new CoordinatesSet($modelCfg->transcribedExons, $modelCfg);

        $this->transcriptExons
            = $this->buildExonsFromCoordset($this->seq, $this->transcriptCoordset);

        $this->transcript = $this->buildTranscript();
        $this->peptide = $this->buildPeptide($modelCfg->initialPhase);
    }

    public function getTranscriptID()
    {
        return $this->transcriptID;
    }

    public function getGeneID()
    {
        if (preg_match('/^(.*)-(.*)$/', $this->transcriptID, $m)) {
            return $m[1];
        }
        return $this->transcriptID;
    }

    public function getFormattedTranscript($eol='<br>')
    {
        $seqID = sprintf(
            "%s_%s_transcript",
            $this->assembly, $this->transcriptID
        );

        return SeqFeature::formatPrint($this->transcript, $seqID, $eol);
    }

    public function getFormattedPeptide($eol='<br>')
    {
        $seqID = sprintf("%s_%s_peptide", $this->assembly, $this->transcriptID);

        return SeqFeature::formatPrint($this->peptide, $seqID, $eol);
    }

    public function getPhases()
    {
        return $this->phases;
    }

    public function getExtractedExons()
    {
        if (isset($this->translatedExons)) {
            return $this->translatedExons;
        }

        $result = array();

        $coordset = $this->cdsCoordset->getCoordset();
        $numCDS = count($this->cdsExons);

        for ($i = 0; $i < $numCDS; $i++) {
            $cdsID = 'CDS_' . ($i + 1);
            $currentPhase = $this->phases[$i];
            $cdsLength = strlen($this->cdsExons[$i]);

            $orientation
                = ($this->isReversed) ? Segment::MINUS_STRAND : Segment::PLUS_STRAND;

            $translation = DNASeq::transeq($this->cdsExons[$i], $currentPhase);

            array_push(
                $result,
                array(
                    $cdsID, $currentPhase,
                    $coordset[$i]->getStartpos(), $coordset[$i]->getEndpos(),
                    $orientation, $cdsLength, SeqFeature::formatPrint($translation)
                )
            );
        }

        $this->translatedExons = $result;

        return $result;
    }

    public function isReversed()
    {
        return $this->isReversed;
    }

    public function getSeq()
    {
        return $this->seq;
    }

    public function getCDSCoordset()
    {
        return $this->cdsCoordset;
    }

    public function getTranscriptCoordset()
    {
        return $this->transcriptCoordset;
    }

    public function getCDSExons()
    {
        return $this->cdsExons;
    }

    public function getTranscriptExons()
    {
        return $this->transcriptExons;
    }

    public function getCDSPepStarts()
    {
        return $this->cdsPepStarts;
    }

    public function getTranscriptSequence()
    {
        return $this->transcript;
    }

    public function getPeptideSequence()
    {
        return $this->peptide;
    }

    public function writeTranscriptFile($tmpfilename)
    {
        $fout = fopen($tmpfilename, "w");
        fwrite($fout, $this->getFormattedTranscript("\n"));
        fclose($fout);
    }

    public function writePeptideFile($tmpfilename)
    {
        $fout = fopen($tmpfilename, "w");
        fwrite($fout, $this->getFormattedPeptide("\n"));
        fclose($fout);
    }

    /*********************
     * Helper Functions
     *********************/
    protected function buildExonsFromCoordset($seq, $coordset)
    {
        $exonslist = array();

        $extractedCoordStr =
            $this->scaffoldRange->getOffsetCoordsStr($coordset->getCoordSet());

        $extractedCoordSet = new CoordinatesSet(
            $extractedCoordStr,
            $this->scaffoldRange->getLength(),
            $this->scaffoldRange->getIsReversed()
        );

        $internalCoords = $extractedCoordSet->getInternalCoords();

        foreach ($internalCoords as $segment) {
            array_push(
                $exonslist,
                $seq->extractSequence(
                    $segment->getStartpos(), $segment->getEndpos()
                )
            );
        }

        return $exonslist;
    }

    protected function calcPepStarts()
    {
        $numIntrons = count($this->cdsExons) - 1;

        $starts = array(0);
        $cumulativeStart = 0;

        for ($i=0; $i<$numIntrons; $i++) {
            $cdsLength = strlen($this->cdsExons[$i]) - $this->phases[$i];
            $aaLength = intval(ceil($cdsLength / DNASeq::CODON_SIZE));

            $cumulativeStart += $aaLength;
            array_push($starts, $cumulativeStart);
        }

        return $starts;
    }

    protected function buildTranscript()
    {
        return join("", $this->transcriptExons);
    }

    protected function buildPeptide($offset)
    {
        $cdsSequence = join("", $this->cdsExons);

        if ($offset !== 0) {
            $cdsSequence = substr($cdsSequence, $offset);
        }

        $cdsSeq = new DNASeq($this->seq->getHeader(), $cdsSequence);

        return $cdsSeq->getDirectTranslation();
    }

    protected function calcPhases($initialPhase=0)
    {
        $phases = array($initialPhase);
        $prev_phase = $initialPhase;

        $coordset = $this->cdsCoordset->getInternalCoords();
        $numIntrons = count($coordset) - 1;

        for ($i = 0; $i < $numIntrons; $i++) {
            $cds = $coordset[$i];

            $numBasesInPhase = $cds->getLength() - $phases[$i];
            $remainingBases = $numBasesInPhase % DNASeq::CODON_SIZE;
            $nextPhase = (DNASeq::CODON_SIZE - $remainingBases) % DNASeq::CODON_SIZE;

            array_push($phases, $nextPhase);
        }

        return $phases;
    }
}
?>
