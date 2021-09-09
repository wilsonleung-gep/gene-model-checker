<?php
class CheckListItem
{
    const PASS = "Pass";
    const SKIP = "Skip";
    const FAIL = "Fail";
    const WARN = "Warn";

    public function __construct($criteria, $options=array())
    {
        $settings = array_merge(
            array(
                "segment" => new Segment(0, 0),
                "status" => self::SKIP,
                "extsequence" => array(),
                "message" => ""
            ), $options
        );

        $this->criteria = $criteria;
        $this->status = $settings["status"];
        $this->segment = $settings["segment"];
        $this->extsequence = $settings["extsequence"];
        $this->message = $settings["message"];
    }

    public function failedCheck()
    {
        return ($this->status === self::FAIL);
    }

    public function getStatus()
    {
        return array(
            $this->criteria, $this->status, $this->segment->toArray(),
            $this->extsequence, $this->message
        );
    }

    protected $criteria;
    protected $status;
    protected $segment;
    protected $extsequence;
    protected $message;
}

?>
