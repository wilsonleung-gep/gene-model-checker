<?php
class Segment
{
    const DELIMITER = "-";
    const PLUS_STRAND = "+";
    const MINUS_STRAND = "-";

    function __construct()
    {
        $numArgs = func_num_args();

        switch($numArgs) {
        case 1:
            $coordStr = func_get_arg(0);
            $this->constructFromString($coordStr);
            break;
        case 2:
            list($startPos, $endPos) = func_get_args();
            $this->constructFromCoords($startPos, $endPos);
            break;
        case 3:
            list($startPos, $endPos, $isReversed) = func_get_args();
            $this->constructFromSpec($startPos, $endPos, $isReversed);
            break;
        default:
            throw new Exception("Invalid number of arguments");
        }
    }

    protected function constructFromString($coordStr)
    {
        $fields = explode(self::DELIMITER, $coordStr);
        if (count($fields) !== 2) {
            throw new Exception("Invalid coordinate string");
        }

        list($startPos, $endPos) = array_map('intval', $fields);

        $this->constructFromCoords($startPos, $endPos);
    }

    protected function constructFromCoords($startPos, $endPos)
    {
        if (($startPos === null) || ($endPos === null)) {
            throw new Exception("Invalid segment coordinates");
        }

        if ($startPos > $endPos) {
            $this->startPos = $endPos;
            $this->endPos = $startPos;
            $this->isReversed = true;
        } else {
            $this->startPos = $startPos;
            $this->endPos = $endPos;
            $this->isReversed = false;
        }
    }

    protected function constructFromSpec($startPos, $endPos, $isReversed)
    {
        if (($startPos === null) || ($endPos === null)) {
            throw new Exception("Invalid segment coordinates");
        }

        $this->startPos = min($startPos, $endPos);
        $this->endPos = max($startPos, $endPos);
        $this->isReversed = $isReversed;
    }

    function __toString()
    {
        return sprintf("%d-%d", $this->startPos, $this->endPos);
    }

    function toArray()
    {
        return array($this->startPos, $this->endPos);
    }

    function isEqualTo($other)
    {
        return ( ($this->startPos === $other->startPos) &&
                ($this->endPos === $other->endPos) &&
                ($this->isReversed === $other->isReversed) );
    }

    function getLength()
    {
        return ($this->endPos - $this->startPos + 1);
    }

    function getStartPos()
    {
        return $this->startPos;
    }

    function getEndPos()
    {
        return $this->endPos;
    }

    function getIsReversed()
    {
        return $this->isReversed;
    }

    function getStrand()
    {
        return ($this->isReversed) ? self::MINUS_STRAND : self::PLUS_STRAND;
    }

    static function compare($a, $b)
    {
        $a_start = $a->getStartPos();
        $b_start = $b->getStartPos();

        if ($a_start === $b_start) {
            return 0;
        }

        return ($a_start < $b_start) ? -1 : 1;
    }

    protected $startPos;
    protected $endPos;
    protected $isReversed;
}
?>
