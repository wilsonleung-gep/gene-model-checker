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
                'password' => $dbConfig["password"],
                "db" => $dbConfig["informantDb"]
            )
        );

        $results = new Results(
            array(
                "data" => retrieveData($db, $validator->clean->protein_name)
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


function retrieveData($db, $proteinName)
{
    $searchPattern = $proteinName."%";
    $results = array();

    $query = <<<SQL
        SELECT FBname, FBid FROM protein_sequences
            WHERE FBname LIKE ?
            ORDER BY LENGTH(FBname), FBname LIMIT 10;
SQL;

    $stmt = $db->prepare($query);

    if (empty($stmt)) {
        throw new Exception("Cannot find protein records");
    }

    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();

    $stmt->store_result();
    $stmt->bind_result($seqname, $fbid);

    while ($stmt->fetch()) {
        array_push(
            $results, array(
                "seqname" => $seqname,
                "fbid" => $fbid
            )
        );
    }

    $stmt->close();

    return $results;
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
        new VType("string", "protein_name", "Protein Name")
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    return $validator;
}

?>
