<?php
class ProteinSeq extends SeqFeature
{
    const SEQTYPE = 'Protein';

    function __construct(
        $header, $sequence, $nucLength, $beginOffset=0, $isReversed=false
    ) {
        parent::__construct($header, $sequence);
        $this->beginOffset = $beginOffset;
        $this->nucLength = $nucLength;
        $this->isReversed = $isReversed;
    }

    function getTransCoords($protCoords)
    {
        return $this->beginOffset + 3 * $protCoords;
    }

    function findPattern($searchPattern, $startlim, $endlim)
    {
        $searchPattern = strtoupper($searchPattern);
        $searchPattern = '/' . $searchPattern . '/';

        $matches = array();
        $offset = 0;

        $matchPatternStatus = preg_match(
            $searchPattern, $this->sequence, $matchArr, PREG_OFFSET_CAPTURE, $offset
        );

        while ($matchPatternStatus) {
            $matchStart = $matchArr[0][1] * 3 + $this->beginOffset;
            $searchLen = strlen($matchArr[0][0]);
            $matchEnd = $matchStart + 3 * ($searchLen - 1) + $this->beginOffset;
            $direction = ($isReversed) ? SeqFeature::FORWARD : SeqFeature::REVERSE;

            if ($this->isReversed) {
                $matchHolder = $matchEnd;
                $matchEnd = $this->nucLength - $matchStart;
                $matchStart = $this->nucLength - $matchHolder;
            }

            if (($revStart >= $startlim) && ($revEnd <= $endlim)) {
                $matches[] = array('matchseq' => $matchArr[0][0],
                    'start' => $revStart, 'end' => $revEnd,
                    'strand' => $direction);
            }

            $offset = $matchArr[0][1] + 1;
        }

        return $matches;
    }

    protected $isReversed;
    protected $beginOffset;
    protected $nucLength;
}

?>
