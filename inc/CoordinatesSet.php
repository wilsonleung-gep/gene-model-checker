<?php
class CoordinatesSet
{
    function __construct()
    {
        $numArgs = func_num_args();

        switch($numArgs) {
        case 2:
            list ($coordStr, $modelCfg) = func_get_args();
            $this->_constructFromSpec(
                $coordStr, $modelCfg->scaffoldLength, $modelCfg->isReversed
            );
            break;
        case 3:
            list($coordStr, $seqLength, $isReversed) = func_get_args();
            $this->_constructFromSpec($coordStr, $seqLength, $isReversed);
            break;
        default:
            throw new Exception("Invalid number of arguments");
        }
    }

    private function _constructFromSpec($coordsStr, $seqLength, $isReversed)
    {
        $this->isReversed = $isReversed;
        $this->seqLength = $seqLength;
        $this->_convertStringToCoordSet($coordsStr, $seqLength, $isReversed);
    }

    function getInternalCoords()
    {
        return $this->coordSet;
    }

    function getCoordSet()
    {
        if (! $this->isReversed) {
            return $this->coordSet;
        }

        $results = array();
        $numCoords = count($this->coordSet);

        for ($i=0; $i<$numCoords; $i++) {
            array_push(
                $results,
                self::getReverseCoordinates(
                    $this->coordSet[$i], $this->seqLength
                )
            );
        }

        return $results;
    }

    function getSegmentByID($idx)
    {
        $segment = $this->coordSet[$idx - 1];

        if ($this->isReversed) {
            return self::getReverseCoordinates($segment, $this->seqLength);
        }

        return $segment;
    }

    function getRange()
    {
        $firstSegment = $this->coordSet[0];
        $lastSegment = end($this->coordSet);

        if ($this->isReversed) {
            $revLastSegment
                = self::getReverseCoordinates($firstSegment, $this->seqLength);

            $revFirstSegment
                = self::getReverseCoordinates($lastSegment, $this->seqLength);

            return new Segment(
                $revFirstSegment->getStartPos(),
                $revLastSegment->getEndPos(),
                $this->isReversed
            );
        }

        return new Segment($firstSegment->getStartPos(), $lastSegment->getEndPos());
    }

    static function getReverseCoordinates($segment, $seqLength)
    {
        $isReversed = true;

        $revStartPos = $seqLength - $segment->getEndPos() + 1;
        $revEndPos = $seqLength - $segment->getStartPos() + 1;

        return new Segment($revStartPos, $revEndPos, $isReversed);
    }

    private function _convertStringToCoordSet(
        $coordsSetString, $seqLength, $isReversed
    ) {
        $this->coordSet = array();

        $coordsSetString = preg_replace("/[\s\t]+/", "", $coordsSetString);
        $coordsList = explode(",", $coordsSetString);

        foreach ($coordsList as $coordsString) {
            $segment = $this->_extractCoordinatesFromString(
                $coordsString, $seqLength, $isReversed
            );

            array_push($this->coordSet, $segment);
        }

        usort($this->coordSet, array('Segment', 'compare'));
    }

    private function _extractCoordinatesFromString($coords, $seqLength, $isReversed)
    {
        $spanCoords = array_map('intval', explode(Segment::DELIMITER, $coords));

        $segment = new Segment($spanCoords[0], $spanCoords[1], $isReversed);

        if ($isReversed) {
            return self::getReverseCoordinates($segment, $seqLength);
        }

        return $segment;
    }

    protected $coordSet;
    protected $seqLength;
    protected $isReversed;
}
?>
