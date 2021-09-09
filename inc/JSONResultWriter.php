<?php
class JSONResultWriter
{
    public function printResult($result)
    {
        echo json_encode(
            array(
                "status" => $result->getStatus(),
                "message" => $result->getMessage(),
                "data" => $result->getResults(),
                "success" => $result->isSuccessful()
            )
        );
    }
}
?>
