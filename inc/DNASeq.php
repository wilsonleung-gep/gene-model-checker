<?php

class DNASeq extends SeqFeature
{
    const ALL_FRAMES = "all";

    const BOTH_STRANDS = "Both";
    const PLUS_STRAND = "Plus";
    const MINUS_STRAND = "Minus";

    const UNKNOWNNT = "N";
    const UNKNOWNAA = "X";
    const STOPAA = "*";

    public function __construct()
    {
        $numargs = func_num_args();

        switch ($numargs) {
        case 1:
            $filename = func_get_arg(0);
            parent::__construct($filename);
            break;

        case 2:
            list($header, $sequence) = func_get_args();
            parent::__construct($header, $sequence);
            break;

        case 3:
            list($header, $sequence, $phase) = func_get_args();
            parent::__construct($header, $sequence, $phase);
            break;

        default:
            throw new Exception("Invalid number of arguments");
        }
    }

    public function reverseComplement($options=array())
    {
        $settings = array_merge(
            array("isReversed" => true, "isComplemented" => true),
            $options
        );

        return self::revcmp(
            $this->sequence, $settings["isReversed"], $settings["isComplemented"]
        );
    }

    public function getReverseComplementSeq($options=array())
    {
        return new DNASeq($this->getHeader(), $this->reverseComplement($options));
    }

    public function getDirectTranslation()
    {
        return self::transeq($this->sequence);
    }

    public function translate($options=array())
    {
        $settings = array_merge(
            array(
                "strands" => self::BOTH_STRANDS,
                "frames" => self::ALL_FRAMES,
                "offset" => 0
            ), $options
        );

        $translations = array();
        $ntSequence = ($settings["offset"] === 0) ? $this->sequence :
            substr($this->sequence, $settings["offset"]);

        $strands = $settings["strands"];
        $frames = $settings["frames"];

        if (($strands === self::PLUS_STRAND) || ($strands === self::BOTH_STRANDS)) {
            $translations[self::PLUS_STRAND]
                = $this->calcFrameTranslations($ntSequence, $frames);
        }

        if (($strands === self::MINUS_STRAND) || ($strands === self::BOTH_STRANDS)) {
            $revcmpSequence = self::revcmp($ntSequence);

            $translations[self::MINUS_STRAND]
                = $this->calcFrameTranslations($revcmpSequence, $frames);
        }

        return $translations;
    }

    static function revcmp($sequence, $isReversed=true, $isComplemented=true)
    {
        $resultSeq = $sequence;

        if ($isComplemented) {
            $resultSeq = strtr($resultSeq, DNASeq::$complementTable);
        }

        if ($isReversed) {
            $resultSeq = strrev($resultSeq);
        }

        return $resultSeq;
    }

    static function transeq($sequence, $offset=0)
    {
        $ntSequence = ($offset === 0) ? $sequence : substr($sequence, $offset);

        $seqLength = strlen($ntSequence);
        $translation = array();

        $codons = str_split($ntSequence, self::CODON_SIZE);

        foreach ($codons as $codon) {
            if (strpos($codon, self::UNKNOWNNT) === false) {
                if ((! empty($codon))
                    && (array_key_exists($codon, self::$translationTable))
                ) {

                    array_push($translation, self::$translationTable[$codon]);

                } else {
                    if (strlen($codon) === self::CODON_SIZE) {
                        array_push($translation, self::UNKNOWNAA);
                    }
                }
            } else {
                array_push($translation, self::UNKNOWNAA);
            }
        }
        return join("", $translation);
    }

    static protected $complementTable = array(
        "A" => "T", "T" => "A",
        "C" => "G", "G" => "C"
    );

    static protected $translationTable = array(
        'TTT' => 'F', 'TTC' => 'F', 'TTA' => 'L', 'TTG' => 'L',
        'CTT' => 'L', 'CTC' => 'L', 'CTA' => 'L', 'CTG' => 'L',
        'ATT' => 'I', 'ATC' => 'I', 'ATA' => 'I', 'ATG' => 'M',
        'GTT' => 'V', 'GTC' => 'V', 'GTA' => 'V', 'GTG' => 'V',
        'TCT' => 'S', 'TCC' => 'S', 'TCA' => 'S', 'TCG' => 'S',
        'CCT' => 'P', 'CCC' => 'P', 'CCA' => 'P', 'CCG' => 'P',
        'ACT' => 'T', 'ACC' => 'T', 'ACA' => 'T', 'ACG' => 'T',
        'GCT' => 'A', 'GCC' => 'A', 'GCA' => 'A', 'GCG' => 'A',
        'TAT' => 'Y', 'TAC' => 'Y', 'TAA' => '*', 'TAG' => '*',
        'CAT' => 'H', 'CAC' => 'H', 'CAA' => 'Q', 'CAG' => 'Q',
        'AAT' => 'N', 'AAC' => 'N', 'AAA' => 'K', 'AAG' => 'K',
        'GAT' => 'D', 'GAC' => 'D', 'GAA' => 'E', 'GAG' => 'E',
        'TGT' => 'C', 'TGC' => 'C', 'TGA' => '*', 'TGG' => 'W',
        'CGT' => 'R', 'CGC' => 'R', 'CGA' => 'R', 'CGG' => 'R',
        'AGT' => 'S', 'AGC' => 'S', 'AGA' => 'R', 'AGG' => 'R',
        'GGT' => 'G', 'GGC' => 'G', 'GGA' => 'G', 'GGG' => 'G'
    );

    protected function calcFrameTranslations($sequence, $frames)
    {
        $results = array();

        if (($frames === self::ALL_FRAMES) || ($frames === 1)) {
            $results[1] = self::transeq($sequence);
        }

        if (($frames === self::ALL_FRAMES) || ($frames === 2)) {
            $results[2] = self::transeq($sequence, 1);
        }

        if (($frames === self::ALL_FRAMES) || ($frames === 3)) {
            $results[3] = self::transeq($sequence, 2);
        }

        return $results;
    }
}

?>
