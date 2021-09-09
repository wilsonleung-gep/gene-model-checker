<?php
require_once dirname(__FILE__)."/VType.php";

class Validator
{
    public function __construct($taintedArray)
    {
        $this->unsafe = $taintedArray;
        $this->errors = array();
        $this->clean = new stdClass();
    }

    public function validate($v)
    {
        if (array_key_exists($v->fieldName, $this->unsafe)) {
            $this->cleanField($v);
        } else {
            if ($v->isRequired) {
                $this->addErrors("Parameter {$v->fieldName} does not exists");
            }
        }
    }

    public function cleanField($v)
    {
        $fieldName = $v->fieldName;

        if (is_bool($this->unsafe[$fieldName])) {
            $this->cleanBoolean($v);
            return;
        }

        $this->unsafe[$fieldName] = trim($this->unsafe[$fieldName]);

        if ($this->isEmpty($this->unsafe[$fieldName])) {
            $this->addErrors("Field {$v->fieldLabel} is empty");
        } else {
            $func = "clean" . ucfirst($v->type);
            $this->$func($v);
        }
    }

    static function cmpCoords($a, $b)
    {
        if ($a["minpos"] === $b["minpos"]) {
            return $b["maxpos"]- $a["maxpos"];
        }

        return $a["minpos"] - $b["minpos"];
    }

    public function checkOverlap($coordsSet, $stopCodon=null)
    {
        if ($stopCodon !== null) {
            array_push($coordsSet, $stopCodon);
        }

        usort($coordsSet, array("Validator", "cmpCoords"));

        $numCoordSet = count($coordsSet);

        for ($i = 1; $i < $numCoordSet; $i++) {
            $current = $coordsSet[$i];
            $previous = $coordsSet[$i-1];

            if ($current["minpos"] <= $previous["maxpos"]) {
                $this->addErrors(
                    sprintf(
                        "Coordinates set %d-%d overlaps with %d-%d",
                        $current["minpos"], $current["maxpos"],
                        $previous["minpos"], $previous["maxpos"]
                    )
                );

                return;
            }
        }
    }

    public function checkFile($fileArray, $v)
    {

        if (! array_key_exists($v->fieldName, $fileArray)) {
            $this->addErrors("{$v->fieldLabel} is not a valid file");
            return;
        }

        $file = $fileArray[$v->fieldName];

        $extensionWhiteList = array("txt", "fasta", "fa", "vcf");

        if (isset($file["error"]) && $file["error"] != 0) {
            $this->addErrors("Error uploading the file: ".$file["error"]);
            return;
        }

        if ((isset($file["size"])) && ($file["size"] == 0)) {
            $this->addErrors("Invalid file: uploaded file is empty");
            return;
        }

        if (!isset($file["tmp_name"]) || (!is_uploaded_file($file["tmp_name"]))) {
            $this->addErrors("Cannot access uploaded file " . $file["error"]);
            return;
        }

        if (!isset($file['name'])) {
            $this->addErrors("Cannot access name of file");
            return;
        }

        $pathInfo = pathinfo($file['name']);
        $fileExtension = $pathInfo["extension"];

        $isValidExtension = false;
        foreach ($extensionWhiteList as $extension) {
            if (strcasecmp($fileExtension, $extension) == 0) {
                $isValidExtension = true;
                break;
            }
        }

        if (!$isValidExtension) {
            $this->addErrors("Invalid file extensions");
            return;
        }

        $this->clean->{$v->fieldName} = $file;
    }

    protected function cleanCoordsetstring($v)
    {
        $fieldName = $v->fieldName;

        $input = preg_replace('/\s+/', '', $this->unsafe[$fieldName]);
        $pattern = '/^(\d+)-(\d+)$/';
        $coordinates = array();

        $fields = explode(",", $input);

        if (count($fields) === 0) {
            $this->addErrors($fieldName." is not a valid coordinate range");
            return;
        }

        foreach ($fields as $field) {
            preg_match($pattern, $field, $matches);
            if (count($matches) !== 3) {
                $this->addErrors($fieldName." is not a valid coordinate range");
                return;
            }

            array_push($coordinates, $matches[1].'-'.$matches[2]);
        }
        $this->clean->{$v->fieldName} = join(',', $coordinates);
    }

    protected function cleanCoordstring($v)
    {
        $fieldName = $v->fieldName;

        $input = preg_replace('/\s+/', '', $this->unsafe[$fieldName]);
        $pattern = '/^(\d+)-(\d+)$/';

        preg_match($pattern, $input, $matches);
        if (count($matches) !== 3) {
            $this->addErrors($fieldName." is not a valid coordinate range");
        } else {
            $this->clean->{$v->fieldName} = $matches[1].'-'.$matches[2];
        }
    }


    protected function cleanString($v)
    {
        $pattern = '/^[a-zA-Z0-9-\(\){}_,.|\s:]+$/';
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($this->checkPattern($pattern, $taintedInput)) {
            $this->clean->{$v->fieldName}
                = filter_var($taintedInput, FILTER_SANITIZE_STRING);

        } else {
            $this->addErrors("Field {$v->fieldLabel} contains illegal characters");
        }
    }

    protected function cleanBoolean($v)
    {
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($taintedInput == true || $taintedInput == false) {
            $this->clean->{$v->fieldName} = $taintedInput;
        } else {
            $this->addErrors("Field {$v->fieldLabel} is not a valid boolean value");
        }
    }

    protected function cleanInt($v)
    {
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($this->checkInt($taintedInput)) {
            $this->clean->{$v->fieldName}
                = filter_var($taintedInput, FILTER_VALIDATE_INT);

        } else {
            $this->addErrors("Field {$v->fieldLabel} is not a valid integer");
        }
    }

    protected function cleanUint($v)
    {
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($this->checkInt($taintedInput)) {
            $val = filter_var($taintedInput, FILTER_VALIDATE_INT);

            if ($val >= 0) {
                $this->clean->{$v->fieldName} = $val;
            } else {
                $this->addErrors(
                    "Field {$v->fieldLabel} must be greater than or equal to 0"
                );
            }
        } else {
            $this->addErrors("Field {$v->fieldLabel} must be an integer");
        }
    }

    protected function cleanBasepos($v)
    {
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($this->checkInt($taintedInput)) {
            $val = filter_var($taintedInput, FILTER_VALIDATE_INT);

            if ($val > 0) {
                $this->clean->{$v->fieldName} = $val;
            } else {
                $this->addErrors(
                    "Field {$v->fieldLabel} must be a positive integer"
                );
            }
        } else {
            $this->addErrors("Field {$v->fieldLabel} must be a positive integer");
        }
    }

    protected function buildCoordset($field, $fieldName)
    {
        $pattern = '/^(\d+)-(\d+)$/';

        preg_match($pattern, $field, $matches);

        if (count($matches) !== 3) {
            throw "${fieldName} is not a valid coordinate range";
        }

        $startpos = intval($matches[1]);
        $endpos = intval($matches[2]);

        return array(
            "minpos" => (($startpos < $endpos) ? $startpos : $endpos),
            "maxpos" => (($startpos < $endpos) ? $endpos : $startpos),
        );
    }

    protected function cleanCoordinates($v)
    {
        $fieldName = $v->fieldName;
        $taintedInput = $this->unsafe[$fieldName];

        try {
            $coordSet = $this->buildCoordset($taintedInput, $fieldName);

            $this->clean->{$fieldName} = $coordSet;
        } catch (Exception $e) {
            $this->addErrors($e->getMessage());
        }
    }

    protected function cleanCoordinatesset($v)
    {
        $fieldName = $v->fieldName;
        $taintedInput = $this->unsafe[$fieldName];

        try {
            $input = preg_replace('/\s+/', '', $taintedInput);

            $coordinates = array();

            $fields = explode(",", $input);

            if (count($fields) === 0) {
                throw "{$fieldName} is not a valid coordinate range";
            }

            foreach ($fields as $field) {
                array_push($coordinates, $this->buildCoordset($field, $fieldName));
            }

            $this->checkOverlap($coordinates);

            $this->clean->{$fieldName} = $coordinates;
        } catch (Exception $e) {
            $this->addErrors($e->getMessage());
        }
    }

    protected function cleanFloat($v)
    {
        $taintedInput = $this->unsafe[$v->fieldName];

        if ($this->checkFloat($taintedInput)) {
            $this->clean->{$v->fieldName}
                = filter_var($taintedInput, FILTER_VALIDATE_FLOAT);

        } else {
            $this->addErrors("Field {$v->fieldLabel} is not a valid float");
        }
    }

    protected function cleanCustom($v)
    {
        $validateFunc = $v->predicate;

        if ($validateFunc === null) {
            throw new Exception("Custom validator requires a predicate");
        }

        $taintedInput = $this->unsafe[$v->fieldName];

        if ($validateFunc($taintedInput)) {
            $this->clean->{$v->fieldName} = $taintedInput;
        } else {
            $this->addErrors("The field {$v->fieldLabel} is invalid");
        }
    }

    protected function getMappedValue($v, $searchArray)
    {
        $fieldName = $v->fieldName;

        $taintedInput = $this->unsafe[$fieldName];

        if (! array_key_exists($taintedInput, $searchArray)) {
            throw new Exception("Unknown value in field {$v->fieldLabel}");
        }

        return $searchArray[$taintedInput];
    }

    protected function cleanPartialmodel($v)
    {
        $partialTypeMap = array(
            "Missing 5' end of translated region" => array(true, false),
            "Missing 3' end of translated region" => array(false, true),
            "Missing 5' and 3' ends of translated region" => array(true, true)
        );

        try {
            $this->clean->{$v->fieldName}
                = $this->getMappedValue($v, $partialTypeMap);

        } catch (Exception $e) {
            $this->addErrors($e->getMessage());
        }
    }

    protected function cleanPhasetype($v)
    {
        $phaseMap = array( 'Phase 0' => 0, 'Phase 1' => 1, 'Phase 2' => 2);

        try {
            $this->clean->{$v->fieldName} = $this->getMappedValue($v, $phaseMap);
        } catch (Exception $e) {
            $this->addErrors($e->getMessage());
        }
    }

    public function addErrors($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }

    public function clearErrors()
    {
        $this->errors = array();
    }

    public function isValid()
    {
        return (count($this->errors) == 0);
    }

    public function hasErrors()
    {
        return ! $this->isValid();
    }

    public function listErrors($separator="<br>")
    {
        return join($separator, $this->errors);
    }


    protected function isEmpty($taintedInput)
    {
        return (strlen(trim($taintedInput)) <= 0);
    }

    protected function checkPattern($pattern, $taintedInput)
    {
        return (preg_match($pattern, $taintedInput) > 0);
    }

    protected function checkInt($taintedInput)
    {
        return ($taintedInput == strval(intval($taintedInput)));
    }

    protected function checkFloat($taintedInput)
    {
        return ($taintedInput == strval(floatval($taintedInput)));
    }

    protected $errors;
    protected $unsafe;
    public $clean;
}
?>
