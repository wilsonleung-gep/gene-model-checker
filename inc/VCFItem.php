<?php
class VCFItem
{
    public $chrom;
    public $position;
    public $id;
    public $referenceSequence;
    public $alternateSequence;
    public $quality;
    public $filter;
    public $info;

    const MIN_REQUIRED_FIELDS = 8;

    function __construct($line)
    {
        $fields = explode("\t", $line);
        $numFields = count($fields);

        if ($numFields < self::MIN_REQUIRED_FIELDS) {
            throw new InvalidArgumentException("Invalid VCF line: {$line}");
        }

        $this->chrom = $fields[0];
        $this->position = intval($fields[1]);
        $this->id = $fields[2];
        $this->referenceSequence = $fields[3];
        $this->alternateSequence = $fields[4];
        $this->quality = $fields[5];
        $this->filter = $fields[6];
        $this->info = $fields[7];
        $this->rest = array();

        if ($numFields > self::MIN_REQUIRED_FIELDS) {
            $this->rest = array_slice($fields, self::MIN_REQUIRED_FIELDS);
        }
    }

    function isWithinRange($scaffoldRange)
    {
        return (
            ($this->chrom === $scaffoldRange->getName()) &&
            ($this->position >= $scaffoldRange->getStartPos()) &&
            ($this->position <= $scaffoldRange->getEndPos())
        );
    }

    function getSizeDifference()
    {
        $altLength = strlen($this->alternateSequence);
        $refLength = strlen($this->referenceSequence);

        return $altLength - $refLength;
    }

    public function __toString()
    {
        $str = join(
            "\t",
            array(
                $this->chrom, $this->position, $this->id,
                $this->referenceSequence, $this->alternateSequence,
                $this->quality, $this->filter, $this->info
            )
        );

        if (count($this->rest) > 0) {
            $str .= join("\t", $this->rest);
        }

        return $str;
    }
}
