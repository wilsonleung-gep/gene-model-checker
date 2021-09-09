<?php
class Build
{
    const PRODUCTION = "production";

    public static function isProduction()
    {
        global $argc, $argv;

        return (isset($argc) && ($argc === 2) && ($argv[1] === self::PRODUCTION));
    }
}
?>
