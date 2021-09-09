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
    $species = $clean->species;
    $matches = array();

    $query = <<<SQL
        SELECT name, description FROM dbDb WHERE active = 1 AND organism = ?
            ORDER BY orderKey;
SQL;

    $stmt = $db->prepare($query);

    if (empty($stmt)) {
        throw new Exception("Cannot find species records");
    }

    $stmt->bind_param("s", $species);
    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($name, $description);

    while ($stmt->fetch()) {
        array_push(
            $matches, array("db" => $name, "assembly" => $description)
        );
    }

    $stmt->close();

    return $matches;
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
        new VType("string", "species", "Species")
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    return $validator;
}

?>
