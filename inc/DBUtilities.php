<?php
class DBUtilities
{
    public function __construct($dbSettings)
    {
        $dbConfig = $this->_loadDbConfig($dbSettings);

        $this->dbconn = new mysqli(
            $dbConfig['hostname'], $dbConfig['username'],
            $dbConfig['password'], $dbConfig['db']
        );

        if (mysqli_connect_errno()) {
            throw new Exception(
                "Cannot connect to the database {$database}: " .
                mysqli_connect_error()
            );
        }
    }

    public function prepare($query)
    {
        $stmt = $this->dbconn->prepare($query);

        if (empty($stmt)) {
            throw new Exception(
                "Error in prepare statement: " . $this->dbconn->error
            );
        }

        return $stmt;
    }

    private function _loadDbConfig($cfg)
    {
        $requiredParams = array('username', 'password', 'db');

        foreach ($requiredParams as $param) {
            if (!isset($cfg[$param])) {
                throw new Exception("Error in database configuration file");
            }
        }

        if (!isset($cfg["hostname"])) {
            $cfg["hostname"] = "localhost";
        }

        return $cfg;
    }

    public function disconnect()
    {
        $this->dbconn->close();
        $this->dbconn = null;
    }

    public function getConn()
    {
        return $this->dbconn;
    }

    protected $dbconn;
}
?>
