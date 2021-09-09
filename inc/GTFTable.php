<?php

class GTFTable
{
    protected $modelCfg;
    protected $trackSettings;

    protected $scaffoldName;
    protected $transcriptFeature;
    protected $geneID;
    protected $transcriptID;
    protected $stopCodon;

    public function __construct(
        $modelCfg, $transcriptFeature, $stopCodon=null, $trackOptions=array()
    ) {
        $assembly = $modelCfg->assembly;
        $description = sprintf("Custom Gene Model for %s", $assembly);

        $this->trackSettings = array_merge(
            array(
                "name" => "CustomModel",
                "description" => $description,
                "color" => "200,0,0",
                "visibility" => Visibility::PACK,
                "group" => "map",
                "priority" => 10
            ), $trackOptions
        );

        $this->modelCfg = $modelCfg;
        $this->scaffoldName = $modelCfg->scaffoldName;
        $this->transcriptFeature = $transcriptFeature;

        $this->geneID
            = sprintf("%s_%s", $assembly, $transcriptFeature->getGeneID());

        $this->transcriptID
            = sprintf("%s_%s", $assembly, $transcriptFeature->getTranscriptID());

        $this->stopCodon = $stopCodon;
    }

    function writeGTFFile($tmpfilename, $header=null)
    {
        $fhOut = fopen($tmpfilename, "w");

        if (!$fhOut) {
            throw new Exception(
                sprintf("Cannot open output file: %s", $tmpfilename)
            );
        }

        if ($header !== null) {
            $this->writeLine($fhOut, $header);
        }

        $this->writeGTFItems($fhOut);

        fclose($fhOut);
    }

    protected function writeGTFItems($fhOut)
    {
        $this->writeTrackHeader($fhOut);

        $this->writeCDSItems($fhOut);

        if ($this->stopCodon !== null) {
            $this->writeStopCodon($fhOut);
        }

        $this->writeExonItems($fhOut);
    }

    protected function writeTrackHeader($fhOut)
    {
        $properties = array("track");

        $propertiesRequiredQuotes = array(
            "name" => 1, "description" => 1, "group" => 1
        );

        foreach ($this->trackSettings as $key => $value) {
            $tpl = (array_key_exists($key, $propertiesRequiredQuotes)) ?
                '%s="%s"' : "%s=%s";

            array_push($properties, sprintf($tpl, $key, $value));
        }

        $this->writeLine($fhOut, join(" ", $properties));
    }

    protected function writeCDSItems($fhOut)
    {
        $phases = $this->transcriptFeature->getPhases();

        $cdsStore = $this->transcriptFeature->getCDSCoordset();
        $cdsCoordset = $cdsStore->getCoordSet();
        $numCDS = count($cdsCoordset);

        for ($i = 0; $i < $numCDS; $i++) {
            $gtfitem = new GTFItem(
                $this->geneID,
                $this->transcriptID,
                array(
                    "seqname" => $this->scaffoldName,
                    "feature" => GTFItem::CDS,
                    "segment" => $cdsCoordset[$i],
                    "frame" => $phases[$i]
                )
            );

            $this->writeGTFItem($fhOut, $gtfitem);
        }
    }

    protected function writeStopCodon($fhOut)
    {
        $gtfitem = new GTFItem(
            $this->geneID,
            $this->transcriptID,
            array(
                "seqname" => $this->scaffoldName,
                "feature" => GTFItem::STOP_CODON,
                "segment" => $this->stopCodon->getSegmentByID(1)
            )
        );

        $this->writeGTFItem($fhOut, $gtfitem);
    }

    protected function writeExonItems($fhOut)
    {
        $exonStore = $this->transcriptFeature->getTranscriptCoordset();
        $exonCoordset = $exonStore->getCoordSet();
        $numExons = count($exonCoordset);

        for ($i = 0; $i < $numExons; $i++) {
            $gtfitem = new GTFItem(
                $this->geneID,
                $this->transcriptID,
                array(
                    "seqname" => $this->scaffoldName,
                    "feature" => GTFItem::EXON,
                    "segment" => $exonCoordset[$i]
                )
            );

            $this->writeGTFItem($fhOut, $gtfitem);
        }
    }

    protected function writeGTFItem($fhOut, $gtfitem)
    {
        $modelCfg = $this->modelCfg;

        if ($modelCfg->hasConsensusErrors) {
            $vcfTable = $modelCfg->vcfTable;

            $originalStart = $gtfitem->getStartPos();
            $startDiff = $vcfTable->calcCumulativeSizeDifference($originalStart);

            $originalEnd = $gtfitem->getEndPos();
            $endDiff = $vcfTable->calcCumulativeSizeDifference($originalEnd);

            $adjStart = $originalStart - $startDiff;
            $adjEnd = $originalEnd - $endDiff;
            $isReversed = $gtfitem->isReversed();

            $gtfitem->setSegment(new Segment($adjStart, $adjEnd, $isReversed));
        }

        $this->writeLine($fhOut, $gtfitem->__toString());
    }

    protected function writeLine($fhOut, $line)
    {
        $line = sprintf("%s\n", $line);

        if (fwrite($fhOut, $line) === false) {
            throw new Exception("Cannot write to GTF file");
        }
    }
}

?>
