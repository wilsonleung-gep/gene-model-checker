<?php
spl_autoload_register(
    function ($className) {
        include '../inc/' . $className . '.php';
    }
);

$APPCONFIG_FILE = "../conf/app.ini.php";
$APPCONFIG = parse_ini_file($APPCONFIG_FILE, true);

function main()
{
    global $APPCONFIG;

    $validator = untaintVariables();
    $db = null;

    try {
        $dbConfig = $APPCONFIG["database"];

        $db = new DBUtilities(
            array(
                "username" => $dbConfig["username"],
                "password" => $dbConfig["password"],
                "db" => $dbConfig["hgcentralDb"]
            )
        );

        $results = new Results(
            array(
                "data" => retrieveData($db, $validator->clean)
            )
        );

        $results->printResult($validator->clean->format);

        $db->disconnect();
    } catch (Exception $e) {
        if (isset($db)) {
            $db->disconnect();
        }

        reportErrors($e->getMessage(), $validator->clean->format);
    }
}

main();


function retrieveData($db, $clean)
{
    if (! isValidDb($db, $clean)) {
        throw new Exception("Cannot find genome database");
    }

    $chromInfoTable = $clean->db.".chromInfo";
    $scaffoldName = $clean->scaffold;
    $scaffoldPattern = $scaffoldName."%";

    $matches = array();

    $query = <<<SQL
        SELECT chrom, size FROM {$chromInfoTable} WHERE chrom LIKE ?
            ORDER BY (chrom = ?) DESC, size DESC, chrom LIMIT 10;
SQL;

    $stmt = $db->prepare($query);

    if (empty($stmt)) {
        throw new Exception("Cannot find scaffold records");
    }

    $stmt->bind_param("ss", $scaffoldPattern, $scaffoldName);
    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($chrom, $size);

    while ($stmt->fetch()) {
        array_push(
            $matches, array("chrom" => $chrom, "size" => $size)
        );
    }

    $stmt->close();

    return $matches;
}

function isValidDb($db, $clean)
{
    $query = "SELECT name FROM dbDb WHERE name = ? AND active = 1 LIMIT 1";

    $stmt = $db->prepare($query);
    $inputDb = $clean->db;

    $stmt->bind_param("s", $inputDb);
    $stmt->execute();

    $stmt->store_result();
    $isValid = ($stmt->num_rows > 0);

    if ($isValid) {
        $stmt->bind_result($db);

        while ($stmt->fetch()) {
            $clean->db = $db;
        }
    }

    $stmt->close();

    return $isValid;
}

function reportErrors($errorMessage, $outputFormat)
{
    $r = new Results(
        array(
            "status" => Results::FAILURE,
            "message" => $errorMessage
        )
    );

    echo $r->printResult($outputFormat);

    exit;
}

function untaintVariables()
{
    $validator = validateVariables();

    if ($validator->hasErrors()) {
        reportErrors($validator->listErrors(), $validator->clean->format);
    }

    return $validator;
}

function validateVariables()
{
    $validator = new Validator($_GET);

    $validator->clean->format = "json";

    $variablesToCheck = array(
        new VType("string", "scaffold", "Scaffold Name"),
        new VType("string", "db", "Genome Database")
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    return $validator;
}

?>
