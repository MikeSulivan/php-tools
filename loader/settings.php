<?php
#Utility-wide Settings
$WSDL_DIR = '..';
$DEFAULT_WSDL = 'zuora-27.0-sandbox-AllOptions.wsdl';
$LOG_FILE = 'log.txt';
$INPUT_FILE = 'input.txt';
$OUTPUT_FILE = 'output.txt';

date_default_timezone_set("America/Los_Angeles");
$SEPARATOR = ",";
$TEXT_QUALIFIER = "\"";
$ID_FIELD = "Id";
$DIRECTORY_SEPARATOR = "/";
if (substr(php_uname(), 0, 7) == "Windows") {
    $DIRECTORY_SEPARATOR = "\\";
}
$baseDir = $WSDL_DIR . $DIRECTORY_SEPARATOR;

$DATE_FORMAT = "Y-m-d\TH:i:s";

$OUTPUT_HEADER = array("Timestamp", "Error Message", "Row", "Error Code", "Batch");
?>