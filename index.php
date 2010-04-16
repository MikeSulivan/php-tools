<?php
include("call-lib.php");

$DEFAULT_WSDL = 'zuora-19.0-sandbox-AllOptions.wsdl';
$SUBSCRIBE_TEMPLATE = array(1=>array(
              			     "version"=>'1.0',
              			     "subscribe"=>'subscribe.xml',
              			     "subscribeWithExistingAccount"=>'subscribeWithExistingAccount.xml',
              			     "queryMore"=>'queryMore.xml'),
                            9=>array(
              			     "version"=>'9.0',
              			     "subscribe"=>'subscribeV9.xml',
              			     "subscribeWithExistingAccount"=>'subscribeWithExistingAccountV9.xml',
              			     "queryMore"=>'queryMore.xml'),
                            11=>array(
              			     "version"=>'11.0',
              			     "subscribe"=>'subscribeV11.xml',
              			     "subscribeWithExistingAccount"=>'',
              			     "queryMore"=>'queryMore.xml')
	                   );

session_start();

// Test whether the form has been submitted.
if (array_key_exists('_submit_check', $_POST)) {
   $_SESSION['method'] = $_POST['method'];
   $_SESSION['body'] = cleanUpXML($_POST['body']);
   $_SESSION['wsdl'] = $_POST['wsdl'];
   $_SESSION['api-ns'] = $_POST['api-ns'];
   $_SESSION['object-ns'] = $_POST['object-ns'];
   $_SESSION['api-batchSize'] = $_POST['api-batchSize'];
   $_SESSION['template-op'] = "";//$_POST['template-op'];
   $_SESSION['template-object'] = "";//$_POST['template-object'];

   // Nuke the session if the Clear button was pressed.
   if (isset($_POST['reset'])) {
      unset($_SESSION['method']);
      unset($_SESSION['body']);
      unset($_SESSION['wsdl']);
      unset($_SESSION['api-ns']);
      unset($_SESSION['object-ns']);
      unset($_SESSION['api-batchSize']);
      unset($_SESSION['template-op']);
      unset($_SESSION['template-object']);
   }
}

// Initialize session if we're starting anew.
if (!isset($_SESSION['method'])) {
   $_SESSION['method'] = "query";
}
if (!isset($_SESSION['body'])) {
   $_SESSION['body'] = "";
}
if (!isset($_SESSION['wsdl'])) {
   $_SESSION['wsdl'] = $DEFAULT_WSDL;
}
if (!isset($_SESSION['api-ns'])) {
   $_SESSION['api-ns'] = "ns1";
}
if (!isset($_SESSION['object-ns'])) {
   $_SESSION['object-ns'] = "ns2";
}
if (!isset($_SESSION['api-batchSize'])) {
   $_SESSION['api-batchSize'] = "0";
}
if (!isset($_SESSION['template-op'])) {
   $_SESSION['template-op'] = "";
}
if (!isset($_SESSION['template-object'])) {
   $_SESSION['template-object'] = "";
}

function cleanUpXML($xml) {
   $retVal = $xml;
   $retVal = str_replace("\\\"", "\"", $retVal);
   $retVal = str_replace("\\'", "'", $retVal);
   return $retVal;
}

function dirList ($directory) 
{
    // create an array to hold directory list
    $results = array();

    // create a handler for the directory
    $handler = opendir($directory);

    // keep going until all files in directory have been read
    while ($file = readdir($handler)) {

        // if $file isn't this directory or its parent, 
        // add it to the results array
        if ($file != '.' && $file != '..') {
	   if (strcasecmp(substr($file,strlen($file) - 4), 'wsdl') == 0)
              $results[] = $file;
	}
    }

    // tidy up: close the handler
    closedir($handler);

    // done!
    return $results;
}

// Do the actual work.
$errorString = "";
$locationString = "";
$requestString = "";
$responseString = "";
$timings = array();

$outputCSV = false;
if ($_POST['csv']) {
   $outputCSV = true;
}

$outputQM = false;
if ($_POST['queryMore']) {
   $outputQM = true;
}

if (isset($_POST['wsdl-download'])) {
   $wsdl = $_SESSION['wsdl'];
   $content = getFileContents($wsdl);
   header("Content-type: text/xml");
   header("Content-disposition: attachment; filename=" . $wsdl . "; size=".strlen($content));
   print $content;
   exit;
}

if (isset($_POST['submit'])) {
    //if (array_key_exists('_submit_check', $_POST)) {

    // get and use remaining arguments
    $username = $_POST['username'];
    $password = $_POST['password'];
    $body = str_replace("\\\"", "\"", $_SESSION['body']);
    $wsdl = $_SESSION['wsdl'];

    // catch query/api confusion.
    if (substr($body,0,1) == "<") {
       if ($_SESSION['method'] == "query") {
           $_SESSION['method'] = "api";
       }
    } else {
       $_SESSION['method'] = "query";
    }
    $method = $_SESSION['method'];

    // process pretty printing
    if ($method == "pp") {
       echo "XML:" . xml_pretty_printer($body, true) . "<br/>";
    }

    if ($method == "query") {
       $payload = "<ns1:query><ns1:queryString>" . $body . "</ns1:queryString></ns1:query>";
    } else {
       $payload = $body;
    }

    if ($method == "query" || $method == "api") {
       	try {
       	    $client = createClient($wsdl, $debug);

       	    $header = login($client, $username, $password, $debug);

       	    $soapRequest = ZuoraAPIHelper::createRequestAndHeadersWithNS($header->data["session"], $_SESSION['api-batchSize'], $payload, $_SESSION['api-ns'], $_SESSION['object-ns']);

            $timeBefore = microtime(true);
	    $xml = ZuoraAPIHelper::callAPIWithClient($client, $header, $soapRequest, $debug);
            $timings[] = microtime(true) - $timeBefore;
	    $xml_obj = ZuoraAPIHelper::getElementFromXML($xml);

	    $uniqueHeaders = array();
       	    if ($outputCSV) {
       	    	header("Content-type: application/vnd.ms-excel");
       	    	header("Content-disposition: csv; filename=document_" . date("Ymd") . ".csv");

		// Get the headers.
                $uniqueHeaders = ZuoraAPIHelper::getCSVHeaders($xml_obj);

	        ZuoraAPIHelper::getCSVData($xml_obj, $uniqueHeaders, true, true);
       	    } else {
       	    	$locationString = $client->myLocation;
       	    	$responseString = xml_pretty_printer($xml, true);
       	    	$requestString = xml_pretty_printer($soapRequest, true);
       	    }

	    $queryLocator = ZuoraAPIHelper::getQueryLocator($xml);
	    while ($outputQM && $queryLocator) {
	        $payload = "<ns1:queryMore><ns1:queryLocator>" . $queryLocator . "</ns1:queryLocator></ns1:queryMore>";
       	    	$soapRequest = ZuoraAPIHelper::createRequestAndHeadersWithNS($header->data["session"], $_SESSION['api-batchSize'], $payload, $_SESSION['api-ns'], $_SESSION['object-ns']);

                $timeBefore = microtime(true);
	    	$xml = ZuoraAPIHelper::callAPIWithClient($client, $header, $soapRequest, $debug);
                $timings[] = microtime(true) - $timeBefore;
	        $queryLocator = ZuoraAPIHelper::getQueryLocator($xml);

       	    	if ($outputCSV) {
	    	    $xml_obj = ZuoraAPIHelper::getElementFromXML($xml);
		    ZuoraAPIHelper::getCSVData($xml_obj, $uniqueHeaders, true, false);
       	    	} else {
       	    	    $responseString .= "\n" . xml_pretty_printer($xml, true);
       	    	    $requestString .= "\n" . xml_pretty_printer($soapRequest, true);
       	    	}
	    }
       	    if ($outputCSV) {
	        exit;
            }
       	} catch (Exception $e) {
       	   $errorString = $e->getMessage();
       	}
    }
}
?>

<html>
 <head>
  <title>Z-Commerce API Utility</title>
 </head>
 <body>
  <form method="post" action=".">
   <input type="hidden" name="_submit_check" value="1"/>

<?php
// Do the actual work.
if (isset($_POST['template'])) {
   $call = $_POST['template-op'];
   $object = $_POST['template-object'];

if (strlen($call) > 0) {
   $_SESSION['method'] = "api";

   try {
      $templateException = false;
      $pos = strpos(strtolower($call),'subscribe');
      if ($pos === false) {
      	 $pos = strpos(strtolower($call),'querymore');
         if ($pos === false) {
      	     $templateException = false;
	 } else {
             $templateException = true;
         }
      } else {
         $templateException = true;
      }

      if (!$templateException) {
         if (strlen($object) > 0) {
            $_SESSION['body'] = printTemplateWithNS($_SESSION['wsdl'], $call, $object, $debug, 0, $_SESSION['api-ns'], $_SESSION['object-ns']);
         } else {
            throw new Exception('Object not specified.');
         }
      } else {
         #Get API version.
         $APIversion = ZuoraAPIHelper::getAPIVersion($_SESSION['wsdl']);
	 $keys = array_keys($SUBSCRIBE_TEMPLATE);
	 sort($keys);
	 $version = 0.0;
	 foreach ($keys as $key) {
	    if ($key <= $APIversion) {
	        $version = $key;
	    } else {
	        break;
            }
	 }

         #Get template.
	 if (file_exists($SUBSCRIBE_TEMPLATE[$version][$call])) {
	     $_SESSION['body'] = getFileContents($SUBSCRIBE_TEMPLATE[$version][$call]);
	 } else {
	     $_SESSION['body'] = "<!-- Template is not available for this API version. -->";
         }
      }
   } catch (Exception $e) {
      $_SESSION['body'] = "<!-- Template not available: " . $e->getMessage() . " -->";
   }
}
}
?>

<table>
<tr><td>

<table>
<tr><td>API Query:</td>
<td><?php if ($_SESSION['method'] == "query") { ?>
<input type="radio" value="query" name="method" checked="true"/>
<?php } else { ?>
<input type="radio" value="query" name="method"/>
<?php } ?>
</td></tr>

<tr><td>API Call:</td>
<td><?php if ($_SESSION['method'] == "api") { ?>
<input type="radio" value="api" name="method" checked="true"/>
<?php } else { ?>
<input type="radio" value="api" name="method"/>
<?php } ?>
</td></tr>

<tr><td>Pretty Print:</td>
<td><?php if ($_SESSION['method'] == "pp") { ?>
<input type="radio" value="pp" name="method" checked="true"/>
<?php } else { ?>
<input type="radio" value="pp" name="method"/>
<?php } ?>
</td></tr>
</table>

</td>
<td>&nbsp;</td>
<td>
<table>
<tr>
<td><input type="submit" value="WSDL" name="wsdl-download"/></td><td><select name="wsdl">
<?php
$wsdl_files = dirList('.');
sort($wsdl_files);
foreach ($wsdl_files as $wsdl_filename) {
   if (strcasecmp($wsdl_filename,$_SESSION['wsdl']) == 0)
      echo "<option value=\"" . $wsdl_filename . "\" selected=\"yes\" >" . $wsdl_filename . "</option>\n";
   else
      echo "<option value=\"" . $wsdl_filename . "\">" . $wsdl_filename . "</option>\n";
}
?>
</select></td></tr>
<tr><td>Username:</td><td><input type="text" size="30" name="username" value="<?php echo $_POST['username'] ?>"/></td></tr>
<tr><td>Password:</td><td><input type="password" size="30" name="password" value=""/></td></tr>
</table>
</td>
<td>&nbsp;</td>
<td>
<table>
<tr><td>API Call Namespaces:</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td>&nbsp;<?php echo $defaultApiNamespaceURL ?></td><td>&nbsp;</td><td><input type="text" size="3" name="api-ns" value="<?php echo $_SESSION['api-ns'] ?>"/></td></tr>
<tr><td>&nbsp;<?php echo $defaultObjectNamespaceURL ?></td><td>&nbsp;</td><td><input type="text" size="3" name="object-ns" value="<?php echo $_SESSION['object-ns'] ?>"/></td></tr>
</table>
</td>

<td>&nbsp;</td>
<td>
<table>
<tr><td>Query Options:</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td>&nbsp;Batch Size&nbsp;<input type="text" size="4" name="api-batchSize" value="<?php echo $_SESSION['api-batchSize'] ?>"/></td></tr>
<tr><td>&nbsp;*&nbsp;100 by default, thru v5.<br/>
&nbsp;*&nbsp;2000 by default w/ QueryMore, v6+.</td></tr>
</table>
</td>
</tr>
</table>

<table>
<tr><td>
Enter your query text or call xml here:<br />
<textarea rows="10" cols="60" name="body" wrap="virtual"><?php echo $_SESSION['body'] ?></textarea><br />
<input type="submit" value="Submit" name="submit"/><input type="submit" value="Clear" name="reset"/>
<input type="checkbox" name="csv">CSV Output&nbsp;
<input type="checkbox" name="queryMore">Use QueryMore to get all results<br />
</td>
<td>
<table>
<tr><td>API Call Templates:</td><td></td></tr>
<tr><td>- Pick an Operation:</td><td><select name="template-op">
<option value=""></option>
<?php
$names = getOperationListFromWSDL($_SESSION['wsdl'], $debug);
foreach ($names as $name) {
      if ($name != "login") {
            echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
      }
}
?>
</select></td></tr>
<tr><td>- Pick an Object:</td><td><select name="template-object">
<option value=""></option>
<?php
$names = getObjectListFromWSDL($_SESSION['wsdl'], $debug);
foreach ($names as $name) {
      if ($name != "zObject") {
            echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
      }
}
?>
</select></td></tr>
<tr><td><input type="submit" value="Get Template" name="template"/></td><td>&nbsp;</td></tr>
</table>
</td>
</tr>
</table>

<?php
// Do the actual work.
if (isset($_POST['submit'])) {
    // Report any errors.
    if ($errorString) {
       echo "<p><b>Exception:</b> " . $errorString . "</p>\n";
       $errorString = "";
    } else {
        if (!$outputCSV) {
            echo "Location: " . $locationString . "<br>\n";
            echo "Response:" . $responseString . "\n";
            echo "Request:" . $requestString . "\n";
            $duration = 0;
            foreach ($timings as $index=>$timing) {
                if (count($timings) > 1) {
                    echo "Timing " . ($index + 1) . ": " . $timing . " secs.<br>\n";
                }
                $duration += $timing;
            }
            echo "Total Duration: " . $duration . " secs.<br>\n";
        }
    }
}
?>
  </form>
 </body>
</html>
