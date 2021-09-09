<?php
class Results
{
    const SUCCESS = 'success';
    const FAILURE = 'failure';

    public function __construct($options=array())
    {
        $defaultSettings = array(
            "status" => self::SUCCESS,
            "success" => true,
            "message" => "",
            "data" => array()
        );

        $settings = array_merge($defaultSettings, $options);

        $this->status = $settings["status"];
        $this->message = $settings["message"];
        $this->data = $settings["data"];
    }


    public function printResult($acceptType)
    {
        $writer = $this->getWriter($acceptType);
        $writer->printResult($this);
    }

    public function updateResult($config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    public function isSuccessful()
    {
        return ($this->status == self::SUCCESS);
    }

    public function appendResult($r, $key=null)
    {
        if ($key == null) {
            array_push($this->data, $r);
        } else {
            $this->data[$key] = $r;
        }
    }

    public function setStatus($predicate)
    {
        $this->status = ($predicate) ? self::SUCCESS : self::FAILURE;
    }

    public function setMessage($msg)
    {
        $this->message = $msg;
    }

    public function setResult($r)
    {
        $this->data = $r;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getResults()
    {
        return $this->data;
    }

    protected function getWriter($acceptType)
    {
        $className = strtoupper($acceptType) . "ResultWriter";
        return new $className();
    }

    protected $status;
    protected $message;
    protected $data;
}
?>
