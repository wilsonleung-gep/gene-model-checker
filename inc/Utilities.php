<?php

class Utilities
{
    static function fetch($prop, $key, $defaultValue=null)
    {
        $hasProperty = isset($prop[$key]);

        if ($hasProperty) {
            return $prop[$key];
        }

        if ($defaultValue === null) {
            throw new InvalidArgumentException("Missing property {$key}");
        }

        return $defaultValue;
    }

    static function runCommand($cmd, $input="")
    {
        $pipes = null;

        $procHandle = proc_open(
            $cmd,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ), $pipes
        );

        if (is_resource($procHandle)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        $returnValue = proc_close($procHandle);

        if (($returnValue !== 0) || ($stderr !== "")) {
            throw new Exception("Return value: {$returnValue}\n{$stderr}");
        }

        return array(
            "stdout" => $stdout,
            "stderr" => $stderr,
            "returnValue" => $returnValue
        );
    }
}
?>
