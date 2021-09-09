<?php
class FileReader
{
    const MAC = "\r";
    const UNIX = "\n";
    const WINDOWS = "\r\n";

    public function __construct($filename)
    {
        $fileContents = file_get_contents($filename);

        if (! $fileContents) {
            throw new Exception(sprintf("Cannot read input file: %s", $filename));
        }

        $eol = self::detectNewLineChar($fileContents);

        if (false !== strpos($fileContents, '{\rtf')) {
            $fileContents = $this->stripRTFTags($fileContents);
        }

        $this->dataLines = explode($eol, trim($fileContents));
    }

    public function getDataSlice($offset=0, $length=null)
    {
        if ($length === null) {
            return array_slice($this->dataLines, $offset);
        }

        return array_slice($this->dataLines, $offset, $length);
    }

    public function getDataLines($idx=null)
    {
        if ($idx !== null) {
            return $this->dataLines[$idx];
        }

        return $this->dataLines;
    }

    static function detectNewLineChar($content)
    {
        if (false !== strpos($content, self::WINDOWS)) {
            return self::WINDOWS;
        } elseif (false !== strpos($content, self::MAC)) {
            return self::MAC;
        }
        return self::UNIX;
    }

    protected function stripRTFTags($fileContents)
    {
        $richTextRegex = array(
            "(\{[\\\\])(.+?)(\})",
            "([\\\\])(.+?)(\b)",
            "[\\\\]",
            "\}",
            "\{"
        );

        $pattern = sprintf('/%s/', join("|", $richTextRegex));

        $plainTextString = preg_replace($pattern, "", $fileContents);

        return trim($plainTextString);
    }

    protected $dataLines;
}
?>
