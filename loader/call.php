<?php
/*    Copyright (c) 2010 Zuora, Inc.
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy of 
 *   this software and associated documentation files (the "Software"), to use copy, 
 *   modify, merge, publish the Software and to distribute, and sublicense copies of 
 *   the Software, provided no fee is charged for the Software.  In addition the
 *   rights specified above are conditioned upon the following:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 *
 *   Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *   or promote products derived from this Software without specific prior written
 *   permission from Zuora, Inc.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *   ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *   (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *   LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *   ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *   (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *   SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
include("../call-lib.php");
include("settings.php");

#File-wide Settings
$debug = 0;          # outputs debug information.
$BATCH_SIZE = 50;
$LOG_COUNT_MULTIPLIER = 10;
$logIndex = $BATCH_SIZE * $LOG_COUNT_MULTIPLIER;

// log file
$lf = fopen($LOG_FILE, 'a') or die("can't open log file");

// Output file
$of = fopen($OUTPUT_FILE, 'a') or die("can't open output file");

// Parameters to be expected:
// 1) WSDL filename
// 2) Username
// 3) Password
// 4) Operation
// 5) Object
// 6) Show request flag

// Output Expected:
// 1) Timestamp
// 2) Error Message
// 3) Account Name
// 4) Charge Name
// 5) Old Quantity
// 6) New Quantity
// 7) Effective Date

// check for all required arguments
// first argument is always name of script!
if ($argc != 7) {
   fwrite($lf,"ERROR: Too few arguments.\n\n");
   fwrite($of, date($DATE_FORMAT, time()).$SEPARATOR.
   	       "ERROR: Too few arguments.".$SEPARATOR.
	       $argc.$SEPARATOR.
	       "".$SEPARATOR.
	       "".$SEPARATOR.
	       ""."\n");
    fclose($of);
    fclose($lf);
    die();
}

// remove first argument
array_shift($argv);

// get wsdl
$wsdl = $argv[0];

// get and use remaining arguments
$username = $argv[1];
$password = $argv[2];
$operation = $argv[3];
$object = $argv[4];
if (strcasecmp($argv[5],"true") == 0) {
    $showRequest = true;
} else {
    $showRequest = false;
}
$fileName = $INPUT_FILE;

fwrite($lf, "WSDL:           " . $wsdl . "\n");
fwrite($lf, "Username:       " . $username . "\n");
fwrite($lf, "Operation:      " . $operation . "\n");
fwrite($lf, "Object:         " . $object . "\n");
fwrite($lf, "Show Request:   " . $showRequest . "\n");
fwrite($lf, "File Name:      " . $fileName . "\n");

$error = false;
$errorMsg = "";

// Start
$timeBefore = microtime(true);
fwrite($lf, "Starting: " . date($DATE_FORMAT, time()) . "\n");
fwrite($of, date($DATE_FORMAT, time()).$SEPARATOR.
	    "Starting " . $operation . " of " . $object . " for " . $username . ".".$SEPARATOR.
	    "".$SEPARATOR.
	    "".$SEPARATOR.
	    "".$SEPARATOR.
	    ""."\n");

if (file_exists($fileName)) {
    try {
        # Open the input file.
    	$handle = fopen($fileName, "r");
        $fieldList = array();
        $dataHeader = array();
        $payload = array();
        $counter = 0;
        $error = false;
        # Load the data.
        while (($line = fgetcsv($handle, 0, $SEPARATOR, $TEXT_QUALIFIER)) !== FALSE) {
            # Load and validate the header.
            if ($counter == 0) {
                $header = $line;
                # Validate the header.
                $fields = ZuoraAPIHelper::getFieldList($wsdl,$object);
            	# Need to add the Id field, since it belongs on the ZObject.
            	array_unshift($fields, $ID_FIELD);
            	$errorFields = array();
            	for ($i = 0; $i < count($header); $i++) {
            	    $found = false;
                    for ($j = 0; $j < count($fields); $j++) {
                        if (strcasecmp(trim($header[$i]), $fields[$j]) == 0) {
                            $found = true;
                        }
                    }
               	    if (!$found) {
               	        $errorFields[] = $header[$i];
               	    }
                }
            	if (count($errorFields) > 0) {
                    fwrite($lf, "WARNING: The following column headers were not recognized for the " . $object . " object: " . implode(", ", $errorFields) . " and will be ignored.\n");
            	}
            	for ($j = 0; $j < count($fields); $j++) {
            	    for ($i = 0; $i < count($header); $i++) {
            	        if (strcasecmp(trim($header[$i]), $fields[$j]) == 0) {
            	    	    $dataHeader[] = array("name"=>$fields[$j], "index"=>$i);
            	    	    $fieldList[] = $fields[$j];
            	    	}
            	    }
                }
            } else {
            	# Capture the data.
            	$rawData = $line;
            	$dataLine = array();
            	foreach ($dataHeader as $column) {
            	    $name = $column["name"];
            	    $index = $column["index"];
            	    # TBD: Validate the data.
            	    $value = "";
            	    if ($index >= 0 && $index < count($rawData)) {
            	       $value = $rawData[$index];
            	    }
            	    $dataLine[$name] = ZuoraAPIHelper::xmlspecialchars(trim($value));
            	}
            	$payload[] = $dataLine;
            }
            if ($error) {
               break;
            }
            $counter++;
        }
	// Close input file.
        fclose($handle);
	fwrite($lf, "Read in " . ($counter - 1) . " total lines of input.\n");

        # Process the data.
        # Make the API call.
        $client = createClient($wsdl, $debug);
        $header = login($client, $username, $password, $debug);

        // Iterate through the data.
	$successCount = 0;
	$errorCount = 0;
	$resultArray = array();
	$payloadChunks = array_chunk($payload, $logIndex);

	$chunkCount = 0;
	$recordCount = 0;
	foreach ($payloadChunks as $chunk) {
	    $chunkCount++;

            # Create the XML for the operation/object.
            $xml = ZuoraAPIHelper::printXMLWithNS($operation, $object, $fieldList, $chunk, $debug, 0, $defaultApiNamespace, $defaultObjectNamespace, false);

            if ($showRequest) {
                $soapRequest = createRequest($header->data["session"], $xml);
	        fwrite($lf, "Request:\n");
                fwrite($lf, xml_pretty_printer($soapRequest, true) . "\n");
		continue;
            }
            $result = ZuoraAPIHelper::bulkOperation($client, $header, $operation, $xml, count($chunk), $debug, TRUE);
	    $successCount += $result["successCount"];
	    $errorCount += $result["errorCount"];
	    $resultArray[] = $result;

	    $recordCount += count($chunk);

            // Print a Summary of results
            fwrite($lf, "Chunk " . $chunkCount . ", " . $recordCount . " records processed: successfully " . $operation . "d " . $result["successCount"] . " out of " . count($chunk) . " ZObjects. Number of errors: " . $result["errorCount"] . ". Total errors: " . $errorCount . "/" . $recordCount . ".\n");
            if ($result["errorCount"] > 0) {
                fwrite($lf, "The following ZObjects failed:\n");
                $errorList = $result["errorList"];
                for ($i = 0; $i < count($errorList); $i++) {
                    $error = $errorList[$i];
                    // Please note the offset by 1 for the header.
                    $recordIndex = $recordCount - count($chunk) + $error["index"] + 1;
                    fwrite($lf, $recordIndex . "," . $error["code"] . "," . $error["message"] . "\n");
	    	    fwrite($of, date($DATE_FORMAT, time()).$SEPARATOR.
	    		    	$error["message"].$SEPARATOR.
	    		    	$recordIndex.$SEPARATOR.
	    		    	$error["code"].$SEPARATOR.
	    		    	$chunkCount."\n");
                }
            }   
        }

        fwrite($lf, "Done.\n");
	fwrite($of, date($DATE_FORMAT, time()).$SEPARATOR.
		    "Done. Successfully " . $operation . "d " . $successCount . " ZObjects. Errors: " . $errorCount . "." .$SEPARATOR.
		    "".$SEPARATOR.
		    "".$SEPARATOR.
		    "".$SEPARATOR.
		    ""."\n");
    } catch (Exception $e) {
        fwrite($lf, "Exception: " . $e->getMessage() . "\n");
        //var_dump($e);
    }
} else {
    fwrite($of, date($DATE_FORMAT, time()).$SEPARATOR.
    	    	"ERROR: File " . $fileName . " not found.".$SEPARATOR.
    	    	"".$SEPARATOR.
    	    	"".$SEPARATOR.
    	    	"".$SEPARATOR.
        	""."\n");
}

fclose($of);
$timeAfter = microtime(true);

fwrite($lf, "Run Time: " . ($timeAfter - $timeBefore) . "\n");
fwrite($lf, "Ended: " . date($DATE_FORMAT, time()) . "\n");

fclose($lf);
die();
?>
