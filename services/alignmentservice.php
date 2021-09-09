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
    try {
        $validator = untaintVariables();
        generateAlignment($validator->clean->q);

    } catch (Exception $e) {
        reportErrors($e->getMessage());
    }
}

main();


function generateAlignment($tmpFilePrefix)
{
    global $APPCONFIG;

    $trashdir = $APPCONFIG["app"]["trashdir"];
    $tmpdir = $APPCONFIG["app"]["rootdir"] . "/" . $trashdir;
    $webtrash = $APPCONFIG["app"]["webroot"] . "/" . $trashdir;

    $alignmentFileName = "align_".$tmpFilePrefix.".html";

    if (is_readable($tmpdir."/".$alignmentFileName)) {
        header(sprintf('Location: %s/%s', $webtrash, $alignmentFileName));
    } else {
        $configFile = $tmpdir."/".$tmpFilePrefix.".info";
        if (! is_readable($configFile)) {
            throw new Exception("Cannot read alignment configuration file");
        }

        $aligner = new ProteinAligner($configFile, $APPCONFIG);
        $aligner->createAlignment($tmpdir."/".$alignmentFileName);

        header(sprintf('Location: %s/%s', $webtrash, $alignmentFileName));
    }
}

function reportErrors($errorMsg)
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No alignments available</title>
  </head>
  <body>
    <b>No alignments have been generated because of the following error:</b>
    <p>${errorMsg}</p>
  </body>
</html>
HTML;

    exit;
}

function untaintVariables()
{
    $validator = validateVariables();

    if ($validator->hasErrors()) {
        reportErrors($validator->listErrors());
    }

    return $validator;
}

function validateVariables()
{
    $validator = new Validator($_GET);

    $variablesToCheck = array(
        new VType("string", "q", "Temporary file name")
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    return $validator;
}

?>
