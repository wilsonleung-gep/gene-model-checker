<?php
class GTFItem
{
    const NA = ".";
    const CDS = "CDS";
    const EXON = "exon";
    const START_CODON = "start_codon";
    const STOP_CODON = "stop_codon";

    function __construct($geneId, $transcriptId, $itemConfig)
    {
        $settings = array_merge(
            array(
                "source" => "GEP",
                "score" => self::NA,
                "frame" => self::NA,
                "attributes" => array(
                    "gene_id" => $geneId,
                    "transcript_id" => $transcriptId
                )
            ), $itemConfig
        );

        $this->seqname = $settings["seqname"];
        $this->source =$settings["source"];
        $this->feature = $settings["feature"];
        $this->segment = $settings["segment"];
        $this->score = $settings["score"];
        $this->frame = $settings["frame"];
        $this->attributes = $settings["attributes"];
    }

    function getStartPos()
    {
        return $this->segment->getStartPos();
    }

    function getEndPos()
    {
        return $this->segment->getEndPos();
    }

    function isReversed()
    {
        return $this->segment->getIsReversed();
    }

    function setSegment($segment)
    {
        $this->segment = $segment;
    }

    function __toString()
    {
        return implode(
            "\t",
            array(
                $this->seqname,
                $this->source,
                $this->feature,
                $this->segment->getStartPos(),
                $this->segment->getEndPos(),
                $this->score,
                $this->segment->getStrand(),
                $this->frame,
                $this->getAttributeString()
            )
        );
    }

    protected function getAttributeString()
    {
        $attributeData = array();
        $tpl = '%s "%s";';

        foreach ($this->attributes as $key => $value) {
            array_push($attributeData, sprintf($tpl, $key, $value));
        }

        return implode(" ", $attributeData);
    }

    protected $seqname;
    protected $source;
    protected $feature;
    protected $segment;
    protected $score;
    protected $frame;
    protected $attributes;
}

?>
