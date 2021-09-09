<?php
class ScaffoldRange
{
    const MAX_EXTRACT_ALL_LENGTH = 100000;
    const PADDING = 100;

    protected $name;
    protected $start;
    protected $end;
    protected $isReversed;
    protected $segments;

    public function __construct(
        $scaffoldName, $scaffoldLength, $isReversed, $transcribedExons
    ) {
        $this->name = $scaffoldName;

        $this->start = 0;
        $this->end = $scaffoldLength;
        $this->isReversed = $isReversed;
        $this->segments = $this->_convertStringToSegments($isReversed, $transcribedExons);

        if ($scaffoldLength > self::MAX_EXTRACT_ALL_LENGTH) {
            $this->calcPaddedRange($scaffoldLength, $isReversed);
        }
    }

    public function __toString()
    {
        return sprintf("%s_%d_%d", $this->name, $this->start, $this->end);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getStartPos()
    {
        return $this->start;
    }

    public function getEndPos()
    {
        return $this->end;
    }

    public function setStartPos($newStartPos)
    {
        $this->start = $newStartPos;
    }

    public function setEndPos($newEndPos)
    {
        $this->end = $newEndPos;
    }

    public function getIsReversed()
    {
        return $this->isReversed;
    }

    public function getLength()
    {
        return $this->end - $this->start;
    }

    public function getOffsetSegments($originalSegments)
    {
        $offsetSegments = array();

        $offset = $this->start;
        $isReversed = $this->isReversed;

        foreach ($segment as $originalSegments) {
            $offsetSegment = new Segment(
                $segment->getStartPos() - $offset,
                $segment->getEndPos() - $offset,
                $isReversed
            );

            array_push($offsetSegments, $offsetSegment);
        }

        return $offsetSegments;
    }

    public function getOffsetCoordsStr($originalSegments)
    {
        $offsetCoords = array();

        $offset = $this->start;

        foreach ($originalSegments as $segment) {
            $offsetStr = sprintf(
                "%d-%d",
                $segment->getStartPos() - $offset,
                $segment->getEndPos() - $offset
            );

            array_push($offsetCoords, $offsetStr);
        }

        return join(",", $offsetCoords);
    }

    protected function calcPaddedRange($scaffoldLength, $isReversed)
    {
        $span = $this->_calcTranscriptSpan($isReversed);

        $this->start = max($span["minPos"] - self::PADDING, 0);
        $this->end = min($span["maxPos"] + self::PADDING, $scaffoldLength);

        $codonSize = SeqFeature::CODON_SIZE;

        $extraStartPadding = ($codonSize - ($this->start % $codonSize)) % $codonSize;
        $this->start -= $extraStartPadding;

        $expectedEndPhase = $scaffoldLength % $codonSize;
        $actualEndPhase = $this->end % $codonSize;
        $extraEndPadding = ($codonSize - ($actualEndPhase - $expectedEndPhase)) % $codonSize;
        $this->end += $extraEndPadding;
    }

    private function _calcTranscriptSpan($isReversed)
    {
        return array(
            "minPos" => $this->segments[0]->getStartPos(),
            "maxPos" => $this->segments[count($this->segments) - 1]->getEndPos(),
            "isReversed" => $isReversed
        );
    }

    private function _convertStringToSegments($isReversed, $coordsSetString) {
        $segments = array();

        $coordsSetString = preg_replace("/[\s\t]+/", "", $coordsSetString);
        $coordsList = explode(",", $coordsSetString);

        foreach ($coordsList as $coordsString) {
            $segment = $this->_extractCoordinatesFromString($coordsString, $isReversed);
            array_push($segments, $segment);
        }

        usort($segments, array('Segment', 'compare'));

        return $segments;
    }

    private function _extractCoordinatesFromString($coords, $isReversed)
    {
        $spanCoords = array_map('intval', explode(Segment::DELIMITER, $coords));

        $segment = new Segment($spanCoords[0], $spanCoords[1], $isReversed);

        return $segment;
    }

}

?>
