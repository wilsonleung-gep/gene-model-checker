<?php
class CheckList
{
    const DONOR = '/GT/';
    const ACCEPTOR = '/AG/';
    const STARTCODON = '/ATG/';
    const STOPCODON = '/TAG|TAA|TGA/';
    const OTHER = 'other';
    const ALTDONOR = '/GC/';

    public function __construct($transcript, $modelCfg)
    {
        $this->listitems = array();

        $this->modelCfg = $modelCfg;
        $this->transcript = $transcript;

        $this->sequence = $transcript->getSeq();

        $this->seqLength = $modelCfg->seqLength;
        $this->scaffoldLength = $modelCfg->scaffoldLength;
        $this->scaffoldRange = $modelCfg->scaffoldRange;
        $this->isReversed = $modelCfg->isReversed;

        $this->internalTranscriptCoordset
            = $transcript->getTranscriptCoordset()->getInternalCoords();

        $this->internalCDSCoordset
            = $transcript->getCDSCoordset()->getInternalCoords();

        $this->partial5 = $modelCfg->missFivePrimeEnd;

        $this->partial3 = $modelCfg->missThreePrimeEnd;
    }

    public function checkSitesSequences($stopCoordset=null)
    {
        if ($this->modelCfg->hasUTR) {
            $this->verifyTranscribedRegions();
        }

        $this->verifyCodingRegions($stopCoordset);
    }

    public function checkInFrameStopCodons()
    {
        $internalStopCheckItem = $this->_findEarlyStops();

        array_push($this->listitems, $internalStopCheckItem);

        if ($internalStopCheckItem->failedCheck()) {
            $this->_checkTranslatedExons();
        }
    }

    public function checkNumCDS($dmelCDScount)
    {
        $coordSet = $this->transcript->getCDSCoordset();
        $modelCDScount = count($this->transcript->getExtractedExons());

        $criteria = "Number of coding exons matched ortholog";
        $properties = array(
            "status" => CheckListItem::PASS,
            "segment" => $coordSet->getRange()
        );

        if ($dmelCDScount !== $modelCDScount) {
            $properties["status"] = CheckListItem::WARN;

            $properties["message"] = ($dmelCDScount === 0) ?
                    sprintf(
                        "Cannot find the ortholog: %s",
                        $this->modelCfg->isoformName
                    ) :
                    sprintf(
                        "Gene model has %d CDS's, ortholog has %d CDS's",
                        $modelCDScount, $dmelCDScount
                    );
        }

        $checklistItem = new CheckListItem($criteria, $properties);

        array_push($this->listitems, $checklistItem);
    }

    public function checkExtraNtInTranslation($transcript, $modelCfg)
    {
        $criteria = sprintf(
            "Length of translated region should be multiples of %d",
            SeqFeature::CODON_SIZE
        );

        $cdsLength = strlen(join("", $transcript->getCDSExons()));

        $inPhaseLength = $cdsLength - $modelCfg->initialPhase;

        $remainingNt = $inPhaseLength % SeqFeature::CODON_SIZE;

        if ($remainingNt != 0) {
            $coordSet = $this->transcript->getCDSCoordset();

            $properties = array(
                "status" => CheckListItem::FAIL,
                "segment" => $coordSet->getRange(),
                "message" => sprintf(
                    "Length of in-phase coding region: %s<br>".
                    "Number of extra nucleotides: %s",
                    $inPhaseLength, $remainingNt
                )
            );

            $checklistItem = new CheckListItem($criteria, $properties);
            array_push($this->listitems, $checklistItem);
        }
    }

    public function addVcfChangedWarning()
    {
        $criteria = "Modified Consensus Sequence";
        $coordSet = $this->transcript->getCDSCoordset();

        $properties = array(
            "status" => CheckListItem::WARN,
            "segment" => $coordSet->getRange(),
            "message" => "Updated consensus sequence based on VCF file"
        );

        $checklistItem = new CheckListItem($criteria, $properties);

        array_push($this->listitems, $checklistItem);
    }


    public function verifyTranscribedRegions()
    {
        $coordSet = $this->internalTranscriptCoordset;

        $numExons = count($coordSet);

        if ($numExons === 1) {
            $this->verifySingleTranscribedExon($coordSet[0]);
            return;
        }

        $this->verifyTranscribedFirstExon($coordSet[0]);

        for ($i = 1; $i < $numExons - 1; $i++) {
            $this->verifyInternalSites($coordSet[$i], $i, 'Exon');
        }

        $this->verifyTranscribedLastExon($coordSet[$numExons - 1], $numExons);
    }

    public function verifyTranscribedFirstExon($exon)
    {
        $donorCriteria = "Donor for Exon 1";
        $acceptorCriteria = "Acceptor for Exon 1";

        if ($this->partial5) {
            $acceptorStart = $exon->getStartPos() - 2;
            $acceptorEnd = $acceptorStart + 1;
            array_push(
                $this->listitems,
                $this->verifySite(
                    $acceptorCriteria, $acceptorStart, $acceptorEnd,
                    CheckList::ACCEPTOR
                )
            );
        }

        $donorStart = $exon->getEndPos() + 1;
        $donorEnd = $donorStart + 1;
        array_push(
            $this->listitems,
            $this->verifySite(
                $donorCriteria, $donorStart, $donorEnd, CheckList::DONOR
            )
        );
    }

    public function verifyTranscribedLastExon($exon, $exonID)
    {
        $donorCriteria = "Donor for Exon {$exonID}";
        $acceptorCriteria = "Acceptor for Exon {$exonID}";

        $acceptorStart = $exon->getStartPos() - 2;
        $acceptorEnd = $acceptorStart + 1;
        array_push(
            $this->listitems,
            $this->verifySite(
                $acceptorCriteria, $acceptorStart, $acceptorEnd,
                CheckList::ACCEPTOR
            )
        );

        if ($this->partial3) {
            $donorStart = $exon->getEndPos() + 1;
            $donorEnd = $donorStart + 1;
            array_push(
                $this->listitems,
                $this->verifySite(
                    $donorCriteria, $donorStart, $donorEnd, CheckList::DONOR
                )
            );
        }
    }

    public function verifySingleTranscribedExon($transcriptExon)
    {
        $singleexonCriteria = 'Transcribed region for single exon gene';

        array_push(
            $this->listitems,
            new CheckListItem(
                $singleexonCriteria,
                array(
                    "message" => "Unable to verify characteristics of a single exon"
                )
            )
        );
    }

    public function verifyCodingRegions($stopCoordSet)
    {
        $coordSet = $this->internalCDSCoordset;

        $numCDS = count($coordSet);

        if ($numCDS === 1) {
            $this->verifySingleExonGene($coordSet[0]);
            return;
        }

        $this->verifyStartSite($coordSet[0]);

        for ($i = 1; $i < $numCDS - 1; $i++) {
            $this->verifyInternalSites($coordSet[$i], $i);
        }

        $this->verifyLastSite($coordSet[$numCDS - 1], $numCDS);

        $this->verifyStopSite($stopCoordSet);
    }

    public function verifyInternalSites($exon, $idx, $type='CDS')
    {
        $idnum = $idx + 1;
        $acceptorCriteria = "Acceptor for " . $type . ' ' . $idnum;
        $donorCriteria = "Donor for " . $type . ' ' . $idnum;

        $acceptorStart = $exon->getStartPos() - 2;
        $acceptorEnd = $acceptorStart + 1;
        array_push(
            $this->listitems,
            $this->verifySite(
                $acceptorCriteria, $acceptorStart, $acceptorEnd,
                CheckList::ACCEPTOR
            )
        );

        $donorStart = $exon->getEndPos() + 1;
        $donorEnd = $donorStart + 1;
        array_push(
            $this->listitems,
            $this->verifySite(
                $donorCriteria, $donorStart, $donorEnd, CheckList::DONOR
            )
        );
    }

    public function verifyStartSite($firstexon)
    {
        $startcodonCriteria = "Check for Start Codon";
        $acceptorCriteria = "Acceptor for CDS 1";
        $donorCriteria = "Donor for CDS 1";

        if ($this->partial5) {
            $firstAcceptorStart = $firstexon->getStartPos() - 2;
            $firstAccepterEnd = $firstAcceptorStart + 1;

            array_push(
                $this->listitems,
                new CheckListItem(
                    $startcodonCriteria,
                    array("message" => "Partial gene with 5' end missing")
                )
            );

            array_push(
                $this->listitems,
                $this->verifySite(
                    $acceptorCriteria, $firstAcceptorStart, $firstAccepterEnd,
                    CheckList::ACCEPTOR
                )
            );

        } else {
            $startcodonStart = $firstexon->getStartPos();
            $startcodonEnd = $startcodonStart + 2;

            $exonEnd = $firstexon->getEndPos();
            $exonSize = $exonEnd - $startcodonStart + 1;

            if ($exonSize < SeqFeature::CODON_SIZE) {
                $message = sprintf("First CDS consists of a Partial Codon: %d bp", $exonSize);

                $peptideSequence = $this->transcript->getPeptideSequence();
                $firstAminoAcid = substr($peptideSequence, 0, 1);

                if ($firstAminoAcid === "M") {
                    array_push(
                        $this->listitems,
                        new CheckListItem(
                            $startcodonCriteria,
                            array("message" => $message, "status" => CheckListItem::PASS)
                        )
                    );
                } else {
                    array_push(
                        $this->listitems,
                        new CheckListItem(
                            $startcodonCriteria,
                            array(
                                "message" => "Found non-canonical amino acid " . $firstAminoAcid,
                                "status" => CheckListItem::FAIL
                            )
                        )
                    );
                }

            } else {
                array_push(
                    $this->listitems,
                    $this->verifySite(
                        $startcodonCriteria, $startcodonStart, $startcodonEnd,
                        CheckList::STARTCODON
                    )
                );
            }

            array_push(
                $this->listitems,
                new CheckListItem(
                    $acceptorCriteria,
                    array("message" => 'Already checked for Start Codon')
                )
            );
        }

        $firstDonorStart = $firstexon->getEndPos() + 1;
        $firstDonorEnd = $firstDonorStart + 1;

        array_push(
            $this->listitems,
            $this->verifySite(
                $donorCriteria, $firstDonorStart, $firstDonorEnd, CheckList::DONOR
            )
        );
    }

    public function verifyLastSite($lastExon, $exonID)
    {
        $acceptorCriteria = "Acceptor for CDS {$exonID}";
        $donorCriteria = "Donor for CDS {$exonID}";

        $lastAcceptorStart = $lastExon->getStartPos() - 2;
        $lastAcceptorEnd = $lastAcceptorStart + 1;
        array_push(
            $this->listitems,
            $this->verifySite(
                $acceptorCriteria, $lastAcceptorStart, $lastAcceptorEnd,
                CheckList::ACCEPTOR
            )
        );

        if ($this->partial3) {
            $lastDonorStart = $lastExon->getEndPos() + 1;
            $lastDonorEnd = $lastDonorStart + 1;

            array_push(
                $this->listitems,
                $this->verifySite(
                    $donorCriteria, $lastDonorStart, $lastDonorEnd, CheckList::DONOR
                )
            );

        } else {
            array_push(
                $this->listitems,
                new CheckListItem(
                    $donorCriteria,
                    array("message" => 'Already checked for Stop Codon')
                )
            );
        }
    }

    public function verifyStopSite($stopCodonSet)
    {
        $stopcodonCriteria = "Check for Stop Codon";

        if ($this->partial3) {
            array_push(
                $this->listitems,
                new CheckListItem(
                    $stopcodonCriteria,
                    array("message" => "Partial gene with 3' end missing")
                )
            );

        } else {
            $stopCodonSet = $stopCodonSet->getInternalCoords();
            $stopCodon = $stopCodonSet[0];

            $stopCodonStart = $stopCodon->getStartPos();
            $stopCodonEnd = $stopCodon->getEndPos();

            array_push(
                $this->listitems,
                $this->verifySite(
                    $stopcodonCriteria, $stopCodonStart, $stopCodonEnd,
                    CheckList::STOPCODON
                )
            );
        }
    }

    public function verifySingleExonGene($firstexon)
    {
        $startcodonCriteria = "Check for Start Codon";
        $stopcodonCriteria = "Check for Stop Codon";
        $acceptorCriteria = "Acceptor for CDS 1";
        $donorCriteria = "Donor for CDS 1";

        if ($this->partial5) {
            $firstAcceptorStart = $firstexon->getStartPos() - 2;
            $firstAcceptorEnd = $firstAcceptorStart + 1;

            array_push(
                $this->listitems,
                new CheckListItem(
                    $startcodonCriteria,
                    array("message" => "Partial gene with 5' end missing")
                )
            );

            array_push(
                $this->listitems,
                $this->verifySite(
                    $acceptorCriteria, $firstAcceptorStart, $firstAcceptorEnd,
                    CheckList::ACCEPTOR
                )
            );

        } else {
            $startcodonStart = $firstexon->getStartPos();
            $startcodonEnd = $startcodonStart + 2;

            array_push(
                $this->listitems,
                $this->verifySite(
                    $startcodonCriteria, $startcodonStart, $startcodonEnd,
                    CheckList::STARTCODON
                )
            );

            array_push(
                $this->listitems,
                new CheckListItem(
                    $acceptorCriteria,
                    array("message" => 'Already checked for Start Codon')
                )
            );
        }

        if ($this->partial3) {
            $lastDonorStart = $firstexon->getEndPos() + 1;
            $lastDonorEnd = $lastDonorStart + 1;

            array_push(
                $this->listitems,
                $this->verifySite(
                    $donorCriteria, $lastDonorStart, $lastDonorEnd, CheckList::DONOR
                )
            );

            array_push(
                $this->listitems,
                new CheckListItem(
                    $stopcodonCriteria,
                    array("message" => "Partial gene with 3' end missing")
                )
            );

        } else {
            $stopcodonStart = $firstexon->getEndPos() + 1;
            $stopcodonEnd = $stopcodonStart + 2;

            array_push(
                $this->listitems,
                new CheckListItem(
                    $donorCriteria,
                    array("message" => 'Already checked for Stop Codon')
                )
            );

            array_push(
                $this->listitems,
                $this->verifySite(
                    $stopcodonCriteria, $stopcodonStart, $stopcodonEnd,
                    CheckList::STOPCODON
                )
            );
        }
    }

    public function extractNearbyRegion($startPos, $endPos, $offset, $extraBases=5)
    {
        $featureLength = $endPos - $startPos + 1;
        $paddedLength = $featureLength + 2 * $extraBases;

        $hasExceededStart = (($startPos - $extraBases) < 0);
        $hasExceededEnd = (($endPos + $extraBases) > $this->scaffoldLength);

        $adjStartPos = $hasExceededStart ? 1 : ($startPos - $extraBases);
        $adjEndPos = $hasExceededEnd ? $this->scaffoldLength : ($endPos + $extraBases);

        $nearbySeq = $this->sequence->extractSequence(
            $adjStartPos - $offset,
            $adjEndPos - $offset
        );

        if ($hasExceededStart) {
            $nearbySeq = str_pad(
                $nearbySeq, $paddedLength, DNASeq::UNKNOWNNT, STR_PAD_LEFT
            );

        } else if ($hasExceededEnd) {
            $nearbySeq = str_pad(
                $nearbySeq, $paddedLength, DNASeq::UNKNOWNNT, STR_PAD_RIGHT
            );
        }

        return $this->_getSurroundingBases($nearbySeq, $featureLength, $extraBases);
    }

    public function verifySite($criteria, $startPos, $endPos, $pattern)
    {
        $status = CheckListItem::PASS;
        $offset = 0;

        if ($this->isReversed) {
            $offset = $this->scaffoldLength - $this->scaffoldRange->getEndPos();
        } else {
            $offset = $this->scaffoldRange->getStartPos();
        }

        $putativeSeq = $this->sequence->extractSequence(
            $startPos - $offset,
            $endPos - $offset
        );

        $properties = array(
            "status" => CheckListItem::PASS,
            "extsequence" => $this->extractNearbyRegion($startPos, $endPos, $offset)
        );

        if (!preg_match($pattern, $putativeSeq)) {
            $properties["status"] = CheckListItem::FAIL;
            $properties["message"] = "Found non-canonical sequence {$putativeSeq}";
        }

        if ((preg_match('/^Donor/', $criteria))
            && (preg_match(self::ALTDONOR, $putativeSeq))
        ) {

            $properties["status"] = CheckListItem::WARN;

            $properties["message"]
                = "Found alternative donor sequence {$putativeSeq}";
        }

        if ($this->isReversed) {
            list($startPos, $endPos) = $this->_flipCoordinates($startPos, $endPos);
        }

        $properties["segment"] = new Segment($startPos, $endPos);

        return new CheckListItem($criteria, $properties);
    }

    public function getStatus()
    {
        return $this->listitems;
    }

    public function getArray()
    {
        $results = array();

        foreach ($this->listitems as $checkitem) {
            array_push($results, $checkitem->getStatus());
        }

        return $results;
    }

    public function getSequence()
    {
        return $this->sequence;
    }

    private function _findEarlyStops()
    {
        $peptideSequence = $this->transcript->getPeptideSequence();

        $criteria = "Additional Checks";
        $properties = array("status" => CheckListItem::PASS);

        $coordset = $this->transcript->getTranscriptCoordset();
        $span = $this->transcript->getCDSCoordset()->getRange();

        if (strpos($peptideSequence, DNASeq::STOPAA) !== false) {
            $properties["status"] = CheckListItem::FAIL;
            $properties["message"] = "Found premature stop codons in translation";
        }

        return new CheckListItem($criteria, $properties);
    }

    private function _checkTranslatedExons()
    {
        $translatedExons = $this->transcript->getExtractedExons();

        list($idIndex, $startIndex, $endIndex, $translationIndex)
            = array(0, 2, 3, 6);

        foreach ($translatedExons as $cdsInfo) {
            $criteria = "Check for in-frame stop codons in ${cdsInfo[$idIndex]}";
            $properties = array("status" => CheckListItem::PASS,
                "segment" => new Segment($cdsInfo[$startIndex], $cdsInfo[$endIndex])
            );

            if (strpos($cdsInfo[$translationIndex], DNASeq::STOPAA) !== false) {
                $properties["status"] = CheckListItem::FAIL;
                $properties["message"] = "Found in-frame stop codons";
            }

            $checkListItem = new CheckListItem($criteria, $properties);

            array_push($this->listitems, $checkListItem);
        }
    }

    private function _flipCoordinates($startpos, $endpos)
    {
        $revStartpos = $this->scaffoldLength - $endpos + 1;
        $revEndpos = $this->scaffoldLength - $startpos + 1;

        return array($revEndpos, $revStartpos);
    }

    private function _getSurroundingBases($nearbySeq, $featureLength, $extraBases)
    {
        $extraBegin = substr($nearbySeq, 0, $extraBases);
        $featureSeq = substr($nearbySeq, $extraBases, $featureLength);
        $extraEnd = substr($nearbySeq, -1 * $extraBases);

        return array($extraBegin, $featureSeq, $extraEnd);
    }

    protected $listitems;
    protected $modelCfg;
    protected $transcript;
    protected $sequence;
}

?>
