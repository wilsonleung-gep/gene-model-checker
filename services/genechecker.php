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

    try {
        $validator = untaintVariables();

        $tmpFilePrefix = str_replace(".", "", uniqid("", TRUE));

        $modelCfg = new ModelConfig($validator->clean, $tmpFilePrefix, $APPCONFIG);

        $stopCodon = ($modelCfg->stopCodon === null) ? null :
                new CoordinatesSet($modelCfg->stopCodon, $modelCfg);

        $transcript = new TranscriptFeature($modelCfg->seq, $modelCfg);

        $alignmentResults = compareOrtholog($transcript, $modelCfg, $tmpFilePrefix);
        $alignmentFiles = $alignmentResults["alignmentFiles"];

        $checklistResults = createChecklist(
            $transcript, $alignmentResults, $stopCodon, $modelCfg
        );

        $gtfTable = new GTFTable($modelCfg, $transcript, $stopCodon);

        $r = new Results(
            array(
                "data" => array(
                    "assembly" => $modelCfg->assembly,
                    "scaffoldname" => $modelCfg->scaffoldName,
                    "informant" => $modelCfg->informant,
                    "isoformname" => $modelCfg->isoformName,
                    "checklist" => $checklistResults->getArray(),
                    "transcript" => $transcript->getFormattedTranscript(),
                    "peptide" => $transcript->getFormattedPeptide(),
                    "transcriptlabels" => createLabels(
                        $transcript->getTranscriptSequence(), true
                    ),
                    "peptidelabels" => createLabels(
                        $alignmentResults["submitted"]["pepSequence"], true
                    ),
                    "extractedexons" => $transcript->getExtractedExons(),
                    "downloadlinks" => createDownloadLinks(
                        $gtfTable, $transcript, $tmpFilePrefix
                    ),
                    "orientation" => ($modelCfg->isReversed) ? 1 : 0,
                    "dpfilename" => $alignmentFiles["dotplotFileName"],
                    "dpUrl" => $alignmentFiles["dotplotDataUrl"],
                    "alignfilename" => $alignmentFiles["alignmentFileName"]
                )
            )
        );

        echo $r->printResult($validator->clean->format);

    } catch (Exception $e) {
        reportErrors($e->getMessage(), "json");
    }
}

main();


function compareOrtholog($transcript, $modelCfg, $tmpFilePrefix)
{
    $isoformName = $modelCfg->isoformName;

    $informantRecord = retrieveInformantRecord($isoformName, $modelCfg);

    $submittedModel = array(
        "pepSequence" => $transcript->getPeptideSequence(),
        "pepStarts" => $transcript->getCDSPepStarts()
    );

    $alignmentFiles = array(
      "dotplotFileName" => '', "dotplotDataUrl" => '', "alignmentFileName" => ''
    );

    if ($informantRecord["cdsCount"] > 0) {
        $alignmentFiles = createAlignments(
            $submittedModel, $informantRecord, $modelCfg, $tmpFilePrefix
        );
    }

    return array(
        "submitted" => $submittedModel,
        "reference" => $informantRecord,
        "alignmentFiles" => $alignmentFiles
    );
}

function createAlignments($submittedModel, $informantRecord, $modelCfg, $tmpFilePrefix)
{
    global $APPCONFIG;

    $trashdir = $APPCONFIG["app"]["trashdir"];
    $tmpdir = $APPCONFIG["app"]["rootdir"] . "/" . $trashdir;
    $webtrash = $APPCONFIG["app"]["webroot"] . "/" . $trashdir;

    $seq1FilePath = $tmpdir . "/" . $tmpFilePrefix . ".q.fasta";
    $seq2FilePath = $tmpdir . "/" . $tmpFilePrefix . ".s.fasta";
    $seq1Starts = join(",", $submittedModel["pepStarts"]);
    $seq2Starts = join(",", $informantRecord["pepStarts"]);

    $seq1FileInfo = array("filePath" => $seq1FilePath,
        "pepStarts" =>$seq1Starts,
        "isCompleteModel" => $modelCfg->isCompleteModel
    );

    $seq2FileInfo = array("filePath" => $seq2FilePath, "pepStarts" => $seq2Starts);

    $seq1 = new SeqFeature(
        sprintf("%s_%s", $modelCfg->informantGenome, $modelCfg->isoformName),
        $informantRecord["pepSequence"]
    );
    $seq1->toFile($seq1FileInfo["filePath"]);

    $seq2 = new SeqFeature(
        sprintf("%s_%s", $modelCfg->assembly, $modelCfg->isoformName),
        $submittedModel["pepSequence"]
    );
    $seq2->toFile($seq2FileInfo["filePath"]);

    $dp = new DotPlotter($seq1FileInfo, $seq2FileInfo, $APPCONFIG);
    $dotplotFileName = "dotplot_" . $tmpFilePrefix . ".png";
    $dp->createDotPlot($tmpdir . "/" . $dotplotFileName);

    file_put_contents(
        $tmpdir."/".$tmpFilePrefix.".info",
        join(
            "\n", array(
                sprintf('seq1Path = "%s"', $seq1FilePath),
                sprintf("seq1Starts = %s", $seq1Starts),
                sprintf('seq2Path = "%s"', $seq2FilePath),
                sprintf("seq2Starts = %s", $seq2Starts)
            )
        )
    );

    $dpJsonFile = $tmpFilePrefix.".json";

    file_put_contents(
        $tmpdir."/".$dpJsonFile,
        json_encode(
            array(
                "query" => array(
                    "id" => $seq1->getHeader(),
                    "sequence" => $seq1->getSequence(),
                    "coords" => $informantRecord["pepStarts"]
                ),
                "subject" => array(
                    "id" => $seq2->getHeader(),
                    "sequence" => $seq2->getSequence(),
                    "coords" => $submittedModel["pepStarts"]
                )
            )
        )
    );

    $dataUrl = $webtrash . '/' . $dpJsonFile;

    return array(
        "dotplotFileName" => $webtrash . '/' . $dotplotFileName,
        "dotplotDataUrl" => $APPCONFIG["app"]["dotplotviewer"].'?dataUrl='.$dataUrl,
        "alignmentFileName" => $APPCONFIG["app"]["alignservice"].'?q='.$tmpFilePrefix
    );
}

function createDownloadLinks($gtfTable, $transcript, $tmpFilePrefix)
{
    global $APPCONFIG;

    $trashdir = $APPCONFIG["app"]["trashdir"];
    $unixTrashPrefix = join(
        "/", array($APPCONFIG["app"]["rootdir"], $trashdir, $tmpFilePrefix)
    );

    $webTrashPrefix = join("/", array($trashdir, $tmpFilePrefix));

    $gtfTable->writeGTFFile($unixTrashPrefix . ".gff");
    $transcript->writeTranscriptFile($unixTrashPrefix . ".fasta");
    $transcript->writePeptideFile($unixTrashPrefix . ".pep");

    return array(
        'gfflink' => $webTrashPrefix . ".gff",
        'transcriptseqlink' => $webTrashPrefix . ".fasta",
        'peptideseqlink' => $webTrashPrefix . ".pep",
        'webroot' => $APPCONFIG["app"]["webroot"]
    );
}

function createChecklist($transcript, $alignmentResults, $stopCodon, $modelCfg)
{
    $checklist = new CheckList($transcript, $modelCfg);

    $checklist->checkSitesSequences($stopCodon);

    $checklist->checkInFrameStopCodons($transcript);

    if (! $modelCfg->missThreePrimeEnd) {
        $checklist->checkExtraNtInTranslation($transcript, $modelCfg);
    }

    if ($modelCfg->isCompleteModel) {
        $checklist->checkNumCDS($alignmentResults["reference"]["cdsCount"]);
    }

    if ($modelCfg->hasConsensusErrors) {
        $checklist->addVcfChangedWarning();
    }

    return $checklist;
}

function createLabels($seq, $includeHeader=false)
{
    $seqLength = strlen($seq);
    $labels = array();

    if ($includeHeader) {
        array_push($labels, ' ');
    }

    $numIterations = ceil($seqLength / SEQFEATURE::LINEWIDTH);

    for ($i = 0; $i < $numIterations; $i++) {
        $idx = 1 + ($i * SEQFEATURE::LINEWIDTH);
        array_push($labels, $idx);
    }

    return join("<br>", $labels);
}

function retrieveInformantRecord($isoformName, $modelCfg)
{
    global $APPCONFIG;

    $db = null;
    $dbConfig = $APPCONFIG["database"];

    $query = <<<SQL
SELECT sequence, cds_count, pep_starts
    FROM protein_sequences WHERE FBname = ? LIMIT 1;
SQL;

    try {
        $db = new DBUtilities(
            array(
                "username" => $dbConfig["username"],
                "password" => $dbConfig["password"],
                "db" => $dbConfig["informantDb"]
            )
        );

        list($pepSequence, $cdsCount, $pepStarts) = array("", 0, null);

        $stmt = $db->prepare($query);

        if (empty($stmt)) {
            throw new Exception("Internal Database error");
        }

        $stmt->bind_param('s', $isoformName);
        $stmt->execute();

        $stmt->store_result();
        $stmt->bind_result($seq, $count, $starts);

        while ($stmt->fetch()) {
            $pepSequence = $seq;
            $cdsCount = $count;
            $pepStarts = array_map('intval', explode(",", $starts));
        }

        $db->disconnect();

        return array(
            "pepSequence" => $pepSequence,
            "cdsCount" => $cdsCount,
            "pepStarts" => $pepStarts
        );

    } catch (Exception $e) {
        if (isset($db)) {
            $db->disconnect();
        }

        reportErrors($e->getMessage());
    }
}

function reportErrors($errorMessage, $outputFormat="json")
{
    $r = new Results(
        array(
            "success" => false,
            "message" => $errorMessage,
            "status" => Results::FAILURE
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

function validOptionFunc($validOptions)
{
    return function ($value) use ($validOptions) {
        return in_array($value, $validOptions);
    };
}

function validateGenomeDb($db, $validator)
{
    $clean = $validator->clean;
    $query = "SELECT name FROM hgcentral.dbDb WHERE name = ? AND active = 1 LIMIT 1";

    $stmt = $db->prepare($query);
    $inputGenomeDb = $clean->genome_db;

    $stmt->bind_param("s", $inputGenomeDb);
    $stmt->execute();

    $stmt->store_result();
    $isValid = ($stmt->num_rows > 0);

    if ($isValid) {
        $stmt->bind_result($genomeDb);

        while ($stmt->fetch()) {
            $clean->genome_db = $genomeDb;
        }
    } else {
        $validator->addErrors("Cannot find genome database");
    }

    return $isValid;
}

function validateScaffoldName($db, $validator)
{
    $clean = $validator->clean;
    $chromInfoTable = $clean->genome_db.".chromInfo";

    $query = <<<SQL
    SELECT chrom, size FROM {$chromInfoTable} WHERE chrom = ?
        ORDER BY size DESC, chrom LIMIT 1;
SQL;

    $stmt = $db->prepare($query);
    $inputScaffoldName = $clean->scaffold_combo;

    $stmt->bind_param("s", $inputScaffoldName);

    $stmt->execute();

    $stmt->store_result();
    $isValid = ($stmt->num_rows > 0);

    if ($isValid) {
        $stmt->bind_result($scaffoldName, $scaffoldLength);

        while ($stmt->fetch()) {
            $clean->scaffold_combo = $scaffoldName;
            $clean->scaffold_length = $scaffoldLength;
        }
    } else {
        $validator->addErrors("Cannot find scaffold");
    }

    return $isValid;
}

function validateDb($validator, $cfg)
{
    $db = null;
    $isValid = false;

    $dbConfig = $cfg["database"];
    $clean = $validator->clean;

    try {
        $db = new DBUtilities(
            array(
                "username" => $dbConfig["username"],
                "password" => $dbConfig["password"],
                "db" => $dbConfig["hgcentralDb"]
            )
        );

        validateGenomeDb($db, $validator) &&
            validateScaffoldName($db, $validator);

        $db->disconnect();

    } catch (Exception $e) {
        if (isset($db)) {
            $db->disconnect();
        }

        reportErrors($e->getMessage());
    }

    return $validator->isValid();
}


function validateVariables()
{
    global $APPCONFIG;

    $validator = new Validator($_POST);

    $validator->clean->format = "json";

    validateRequiredParams($validator) &&
        validateDb($validator, $APPCONFIG) &&
        validateCoordinatesParams($validator) &&
        validateUploadFiles($validator);

    return $validator;
}

function validateRequiredParams($validator)
{
    $variablesToCheck = array(
        new VType("string", "genome_db", "Genome Database"),

        new VType("string", "scaffold_combo", "Scaffold Name"),

        new VType("string", "ortholog_name", "Ortholog Accession"),

        new VType(
            "custom", "has_vcf", "Has Consensus Errors",
            true, validOptionFunc(array("yes", "no"))
        ),

        new VType("coordinatesset", "coding_exons", "Coding Exons Coordinates"),

        new VType(
            "custom", "annotated_utr", "Annotated UTR",
            true, validOptionFunc(array("yes", "no"))
        ),

        new VType(
            "custom", "orientation", "Orientation",
            true, validOptionFunc(array("plus", "minus"))
        ),

        new VType(
            "custom", "model", "Complete Model",
            true, validOptionFunc(array("complete", "partial"))
        )
    );

    foreach ($variablesToCheck as $v) {
        $validator->validate($v);
    }

    return $validator->isValid();
}

function validateCoordinatesParams($validator)
{
    $clean = $validator->clean;

    if ($clean->annotated_utr === "yes") {
        $validator->validate(
            new VType(
                "coordinatesset", "transcribed_exons",
                "Transcribed Exons Coordinates"
            )
        );
    }

    if ($clean->model === "partial") {
        $validator->validate(
            new VType("partialmodel", "partial_type", "Partial Gene")
        );

        if ($validator->hasErrors()) {
            return false;
        }

        if ($clean->partial_type[0] === true) {
            $validator->validate(
                new VType("phasetype", "phase_info", "Phase of first CDS")
            );
        }

        if ($clean->partial_type[1] === false) {
            $validator->validate(
                new VType("coordinates", "stop_codon", "Stop Codon")
            );
        }
    } else {
        $validator->validate(new VType("coordinates", "stop_codon", "Stop Codon"));
    }

    if ($validator->hasErrors()) {
        return false;
    }

    if (($clean->model === "complete") || ($clean->partial_type[1] === false)) {
        $validator->checkOverlap($clean->coding_exons, $clean->stop_codon);
    }

    if (($clean->model === "complete") || ($clean->partial_type[1] === false)) {
        $validator->validate(new VType("coordstring", "stop_codon", "Stop Codon"));
    }

    $validator->validate(
        new VType("coordsetstring", "coding_exons", "Coding Exons Coordinates")
    );

    if ($validator->clean->annotated_utr === "yes") {
        $validator->validate(
            new VType(
                "coordsetstring", "transcribed_exons",
                "Transcribed Exons Coordinates"
            )
        );
    }

    return $validator->isValid();
}

function validateUploadFiles($validator)
{
    if ($validator->clean->has_vcf === "yes") {
        $validator->checkFile($_FILES, new VType("file", "vcffile", "VCF file"));
    }

    return $validator->isValid();
}

?>
