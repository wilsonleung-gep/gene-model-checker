<?php
class VCFTable
{
    protected $changes;

    public function __construct($content, $scaffoldRange=null)
    {
        $this->headers = array();
        $this->changes = array();

        $this->loadVCFContent($content, $scaffoldRange);
    }

    public function convertCoordinatesString($coordsSetString)
    {
        $newCoordsList = array();

        $coordsSetString = preg_replace("/[\s\t]+/", "", $coordsSetString);
        $coordsList = explode(",", $coordsSetString);

        foreach ($coordsList as $coords) {
            $span = array_map('intval', explode(Segment::DELIMITER, $coords));
            if (count($span) !== 2) {
                throw new InvalidArgumentException("Incorrect coordinates format");
            }

            $spanStart = $span[0] + $this->calcCumulativeSizeDifference($span[0]);
            $spanEnd   = $span[1] + $this->calcCumulativeSizeDifference($span[1]);

            array_push($newCoordsList, sprintf("%d-%d", $spanStart, $spanEnd));
        }

        return implode(",", $newCoordsList);
    }

    public function calcCumulativeSizeDifference($originalPosition)
    {
        $totalDifference = 0;

        $numVcfItems = count($this->changes);

        for ($i=0; $i<$numVcfItems; $i++) {
            $vcfItem = $this->changes[$i];

            if ($vcfItem->position > $originalPosition) {
                break;
            }

            $totalDifference += $vcfItem->getSizeDifference();
        }

        return $totalDifference;
    }

    public function getTotalSizeDifference()
    {
        $totalDifference = 0;

        foreach ($this->changes as $vcfItem) {
            $totalDifference += $vcfItem->getSizeDifference();
        }

        return $totalDifference;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    protected function loadVCFContent($content, $scaffoldRange)
    {
        $firstLine = $content[0];

        if (strpos($firstLine, "##fileformat=VCF") !== 0) {
            throw new InvalidArgumentException("Invalid VCF file");
        }

        foreach ($content as $line) {
            if ((strpos($line, "#") === 0) &&
                (strpos($line, "##contig") !== 0)) {

                array_push($this->headers, $line);
                continue;
            }

            $item = new VCFItem($line);

            if (($scaffoldRange !== null) &&
                ($item->isWithinRange($scaffoldRange))) {

                array_push($this->changes, $item);
            }
        }

        usort($this->changes, "VCFTable_sortByPosition");
    }

    public static function loadChangesFromFile($filename, $scaffoldRange=null)
    {
        $reader = new FileReader($filename);

        return new VCFTable($reader->getDataLines(), $scaffoldRange);
    }

    public function writeVCFFile($vcfOutFile)
    {
        file_put_contents(
            $vcfOutFile,
            join(
                "\n",
                array_merge($this->headers, $this->changes)
            ). "\n"
        );
    }
}

function VCFTable_sortByPosition($a, $b)
{
    if ($a->chrom === $b->chrom) {
        if ($a->position === $b->position) {
            return 0;
        } else {
            return ($a->position < $b->position) ? -1 : 1;
        }
    }

    return ($a->chrom < $b->chrom) ? -1 : 1;
}
