<?php
class SeqFeature
{
    const LINEWIDTH = 60;
    const CODON_SIZE = 3;

    public function __construct()
    {
        $numargs = func_num_args();

        switch ($numargs) {
        case 1:
            $filename = func_get_arg(0);
            $this->constructFromFile($filename);
            break;

        case 2:
            list($firstArg, $secondArg) = func_get_args();

            if (is_int($secondArg)) {
                $this->constructFromFile($firstArg, $secondArg);
            } else {
                $this->constructFromSpec($firstArg, $secondArg);
            }
            break;

        case 3:
            list($header, $sequence, $phase) = func_get_args();
            $this->constructFromSpec($header, $sequence, $phase);
            break;

        default:
            throw new Exception("Invalid number of arguments");
        }
    }

    public function extractSequence($startPos, $endPos=null)
    {
        if (($endPos === null) || ($endPos > $this->seqLength)) {
            $endPos = $this->seqLength;
        }

        $startIdx = $startPos - 1;
        $extractedLength = $endPos - $startIdx;

        return substr($this->sequence, $startIdx, $extractedLength);
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getSequence()
    {
        return $this->sequence;
    }

    public function getPhase()
    {
        return $this->phase;
    }

    public function getLength()
    {
        return $this->seqLength;
    }

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function prettyPrint($eol="<br>", $charsPerLine=self::LINEWIDTH)
    {
        return self::formatPrint(
            $this->sequence, $this->header, $eol, $charsPerLine
        );
    }

    public function toFile($tmpfilePath)
    {
        $output = fopen($tmpfilePath, "w");
        fwrite($output, $this->prettyPrint("\n"));
        fclose($output);
    }

    static function formatPrint(
        $sequence, $header=null, $eol="<br>", $charsPerLine=self::LINEWIDTH
    ) {
        $wrappedText = wordwrap($sequence, $charsPerLine, $eol, true);

        if ($header === null) {
            return $wrappedText;
        }

        $tpl = ">%s%s%s";
        if (strlen($sequence) % $charsPerLine !== 0) {
            $tpl .= $eol;
        }

        return sprintf($tpl, $header, $eol, $wrappedText);
    }

    protected function constructFromSpec($defline, $sequence, $phase=0)
    {
        $this->header = $this->parseHeader($defline);
        $this->sequence = $sequence;
        $this->phase = $phase;

        $this->seqLength = strlen($sequence);
    }

    protected function constructFromFile($filename, $phase=0)
    {
        $reader = new FileReader($filename);

        $offset = 0;
        $firstLine = $reader->getDataLines(0);

        if (preg_match("/^>(.*)$/", $firstLine, $match)) {
            $this->header = $this->parseHeader($match[1]);
            $offset += 1;
        }

        $this->sequence = strtoupper(join("", $reader->getDataSlice($offset)));
        $this->seqLength = strlen($this->sequence);
        $this->phase = $phase;
    }

    protected function parseHeader($description)
    {
        $headerFields = explode(" ", $description);
        $seqid = $headerFields[0];

        return $seqid;
    }

    protected $header;
    protected $sequence;
    protected $phase;
    protected $seqLength;
}
?>
