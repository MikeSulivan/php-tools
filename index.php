<?php
/* Copyright (c) 2011 Zuora, Inc.
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of
* this software and associated documentation files (the "Software"), to use copy,
* modify, merge, publish the Software and to distribute, and sublicense copies of
* the Software, provided no fee is charged for the Software. In addition the
* rights specified above are conditioned upon the following:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* Zuora, Inc. or any other trademarks of Zuora, Inc. may not be used to endorse
* or promote products derived from this Software without specific prior written
* permission from Zuora, Inc.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
* ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
* ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

include("call-lib.php");

$DEFAULT_WSDL = 'zuora-29.0-sandbox-AllOptions.wsdl';
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
              			     "queryMore"=>'queryMore.xml',
                                     "generate"=>'generate.xml'),
                            25=>array(
              			     "version"=>'25.0',
              			     "subscribe"=>'subscribeV25.xml',
              			     "subscribeWithExistingAccount"=>'',
              			     "queryMore"=>'queryMore.xml',
                                     "generate"=>'generate.xml')
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
   $_SESSION['service_url'] = $_POST['service_url'];
   if (strlen($_SESSION['service_url']) <= 0) {
      unset($_SESSION['service_url']);
   }
   if ($_POST['sessionId-refresh']) {
      $_SESSION['sessionId-refresh'] = true;
   } else {
      $_SESSION['sessionId-refresh'] = false;
   }
   $_SESSION['sessionId'] = $_POST['sessionId'];

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
      unset($_SESSION['service_url']);
      unset($_SESSION['sessionId-refresh']);
      unset($_SESSION['sessionId']);
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
if (!isset($_SESSION['service_url'])) {
   $_SESSION['service_url'] = ZuoraAPIHelper::getSoapAddress($_SESSION['wsdl']);
}
if (!isset($_SESSION['sessionId-refresh'])) {
   $_SESSION['sessionId-refresh'] = true;
}
if (!isset($_SESSION['sessionId-refresh'])) {
   $_SESSION['sessionId'] = "";
}

if (!isset($_SESSION['sessionStartTime'])) {
   $_SESSION['sessionStartTime'] = microtime(true);
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
    $body = $_SESSION['body'];
    $wsdl = $_SESSION['wsdl'];

    // catch query/api confusion.
    if (substr(trim($body),0,1) == "<") {
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
       $payload = "<ns1:query><ns1:queryString>" . htmlspecialchars($body) . "</ns1:queryString></ns1:query>";
    } else {
       $payload = $body;
    }

    $callOptions = array();
    if ($_POST['api-singleTxn']) {
        $callOptions = array("useSingleTransaction"=>$_POST['api-singleTxn']);
    }

    if ($method == "query" || $method == "api") {
       	try {
       	    $client = createClient($wsdl, $debug);
            $client->setLocation($_SESSION['service_url']);
       	    $locationString = $client->myLocation;
       	    $header = ZuoraAPIHelper::getHeader('');
            if (!$_SESSION['sessionId-refresh']) {
                $header->data["session"] = $_SESSION['sessionId'];
            } else {
                $header = ZuoraAPIHelper::login($client, $username, $password, $debug);
                if ($header->data["session"] == NULL) {
                    throw new Exception("Null session received, please check your username or password.");
                }
                //$_SESSION['sessionId-refresh'] = false;
                $_SESSION['sessionStartTime'] = microtime(true);
                $_SESSION['sessionId'] = $header->data["session"];
            }
            
       	    $soapRequest = ZuoraAPIHelper::createRequestAndHeadersWithNS($header->data["session"], $_SESSION['api-batchSize'], $callOptions, $payload, $_SESSION['api-ns'], $_SESSION['object-ns']);
       	    $requestString = xml_pretty_printer($soapRequest, true);

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
       	    	$responseString = xml_pretty_printer($xml, true);
       	    }

	    $queryLocator = ZuoraAPIHelper::getQueryLocator($xml);
	    while ($outputQM && $queryLocator) {
	        $payload = "<ns1:queryMore><ns1:queryLocator>" . $queryLocator . "</ns1:queryLocator></ns1:queryMore>";
       	    	$soapRequest = ZuoraAPIHelper::createRequestAndHeadersWithNS($header->data["session"], $_SESSION['api-batchSize'], $callOptions, $payload, $_SESSION['api-ns'], $_SESSION['object-ns']);

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
 <!-- 2010/12/13 Fernando - Added 3 links for Pop-up Window  -->
	<link rel='Shortcut Icon' href='http://apidocs.developer.zuora.com/favicon.ico' type='image/x-icon' />
    <link rel='Bookmark' href='http://apidocs.developer.zuora.com/favicon.ico' type='image/x-icon' />
 <!-- 2010/12/21 Fernando - Added Style -->
  <style type="text/css">
  span.button input {
        background:none;
	border:0;
	margin:0;
	padding:0;
	height:0;
        line-height:0;
	font-size:0;
	display:block;
  }	
  </style>
  <script type="text/javascript">
<!-- Function to control new documentation PopUp 
function popup(url) 
{
 var width  = screen.width/2;
 var height = screen.height/2;
 var left   = (screen.width  - width)/2;
 var top    = (screen.height - height)/2;
 var params = 'width='+width+', height='+height;
 params += ', top='+top+', left='+left;
 params += ', directories=no';
 params += ', location=no';
 params += ', menubar=no';
 params += ', resizable=yes';
 params += ', scrollbars=yes';
 params += ', status=no';
 params += ', toolbar=no';
 newwin=window.open('http://apidocs.developer.zuora.com/index.php/'+url,'windowname5', params);
 if (window.focus) {newwin.focus()}
 return false;
}
// -->
</script>
 </head>
 
 <body >
 <!-- 2010/12/21 Fernando - Added Header and Horizontal Separator-->
 <table width="99%" border="0" cellpadding="0" cellspacing="0" align="center">
        <tr>
                <td id="Title" height="47px" bgcolor="#5e9633" style="background-image: url('http://apidocs.developer.zuora.com/skins/zuora/top_bg.gif');background-repeat: repeat-x; background-position: center;" width="100%" align="center"><font color="white"><b>Z•Commerce API Utility</b></font></td>
        </tr>
 </table>
 <table width="99%" border="0" cellpadding="0" cellspacing="0" align="center"> 
        <tr>
                <td><hr /></td>
        </tr>
        <tr align="center">
                <form name="main" method="post" action=".">
                <input tabindex="100" type="hidden" name="_submit_check" value="1"/>
                <span class="button"><input tabindex="101" type="submit" name="submit" value="" /></span>
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
      if ($pos === false && !$templateException) {
         $templateException = false;
      } else {
         $templateException = true;
      }
      $pos = strpos(strtolower($call),'querymore');
      if ($pos === false && !$templateException) {
         $templateException = false;
      } else {
         $templateException = true;
      }
      $pos = strpos(strtolower($call),'generate');
      if ($pos === false && !$templateException) {
         $templateException = false;
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
         $_SESSION['body'] = "version: " . $version . " call: " . $call . " done.\n";
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

                <table width="99%" border="0" cellpadding="0" cellspacing="0" align="center">
                        <tr>
                                <td>
                                        <table>
                                                <tr>
                                                        <td>API Query:</td>
                                                        <td><?php if ($_SESSION['method'] == "query") { ?>
                                                                <input tabindex="1" type="radio" value="query" name="method" checked="true"/>
                                                                <?php } else { ?>
                                                                <input tabindex="1" type="radio" value="query" name="method"/>
                                                                <?php } ?>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <td>API Call:</td>
                                                        <td><?php if ($_SESSION['method'] == "api") { ?>
                                                                <input tabindex="2" type="radio" value="api" name="method" checked="true"/>
                                                                <?php } else { ?>
                                                                <input tabindex="2" type="radio" value="api" name="method"/>
                                                                <?php } ?>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <td>Pretty Print:</td>
                                                        <td><?php if ($_SESSION['method'] == "pp") { ?>
                                                                <input tabindex="3" type="radio" value="pp" name="method" checked="true"/>
                                                                <?php } else { ?>
                                                                <input tabindex="3" type="radio" value="pp" name="method"/>
                                                                <?php } ?>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <td>&nbsp;</td>
                                                        <td>&nbsp;</td>
                                                </tr>
                                        </table>
                                </td>
                                <td>&nbsp;</td>
                                <td>
                                        <table>
                                                <tr>
                                                        <td><input tabindex="99" type="submit" value="WSDL" name="wsdl-download"/></td>
                                                        <td><select tabindex="4" name="wsdl" onchange="document.main.service_url.value=''">
                                                                <?php
                                                                $wsdl_files = dirList('.');
                                                                function cmp($a, $b) {
                                                                    $prefix = '\-';
                                                                    $av = substrpos($a, $prefix, '.') * -1;
                                                                    $bv = substrpos($b, $prefix, '.') * -1;
                                                                        if ($av == $bv) {
                                                                                if ($a == $b) {
                                                                                        return 0;
                                                                                }
                                                                        return ($a < $b) ? -1 : 1;
                                                                    }
                                                                    // Reverse sort.
                                                                    return ($av > $bv) ? -1 : 1;
                                                                }
                                                                usort($wsdl_files, 'cmp');
                                                                foreach ($wsdl_files as $wsdl_filename) {
                                                                   if (strcasecmp($wsdl_filename,$_SESSION['wsdl']) == 0)
                                                                      echo "<option value=\"" . $wsdl_filename . "\" selected=\"yes\" >" . $wsdl_filename . "</option>\n";
                                                                   else
                                                                      echo "<option value=\"" . $wsdl_filename . "\">" . $wsdl_filename . "</option>\n";
                                                                }
                                                                ?>
                                                                </select>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <td>Username:</td>
                                                        <td><input tabindex="5" type="text" size="30" name="username" value="<?php echo $_POST['username'] ?>"/></td>
                                                </tr>
                                                <tr>
                                                        <td>Password:</td>
                                                        <td><input tabindex="6" type="password" size="30" name="password" value=""/></td>
                                                </tr>
                                                <tr>
                                                        <td>Session Id:</td>
                                                        <td><input tabindex="97" type="sessionId" size="40" name="sessionId" value="<?php echo $_SESSION['sessionId'] ?>"/>&nbsp;Refresh?
<?php if (!$_SESSION['sessionId-refresh']) { ?>
<input tabindex="98" type="checkbox" name="sessionId-refresh" value="true"/>
<? } else { ?>
<input tabindex="98" type="checkbox" name="sessionId-refresh" value="true" checked="true"/>
<? } ?>
</td></tr>
<tr><td>Session Age:</td><td><?php echo round((microtime(true) - $_SESSION['sessionStartTime'])/60, 2) ?> minutes.</td></tr>

                                        </table>
                                </td>
                                <td>&nbsp;</td>
                                <td>
                                        <table>
                                                <tr>
                                                        <td>Location:&nbsp;<input tabindex="103" type="text" size="55" name="service_url" value="<?php echo $_SESSION['service_url'] ?>"/></td>
                                                </tr>
                                                <tr>
                                                        <td>
                                                                <table>
                                                                        <tr>
                                                                                <td>
                                                                                        <table>
                                                                                                <tr>
                                                                                                        <td>Query Batch Size&nbsp;<input tabindex="104" type="text" size="4" name="api-batchSize" value="<?php echo $_SESSION['api-batchSize'] ?>"/></td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                        <td>&nbsp;*&nbsp;Default thru v5 100, v6+ 2000.</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                        <td><input tabindex="105" type="checkbox" name="api-singleTxn" value="true"/>&nbsp;Call Options: Single Transaction</td>
                                                                                                </tr>
                                                                                        </table>
                                                                                </td>
                                                                                <td>&nbsp;</td>
                                                                                <td>
                                                                                        <table>
                                                                                                <tr>
                                                                                                        <td>API Call Namespaces:</td>
                                                                                                        <td>&nbsp;</td>
                                                                                                        <td>&nbsp;</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                        <td>&nbsp;<?php echo $defaultApiNamespaceURL ?></td>
                                                                                                        <td>&nbsp;</td>
                                                                                                        <td><input tabindex="107" type="text" size="3" name="api-ns" value="<?php echo $_SESSION['api-ns'] ?>"/></td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                        <td>&nbsp;<?php echo $defaultObjectNamespaceURL ?></td>
                                                                                                        <td>&nbsp;</td>
                                                                                                        <td><input tabindex="108" type="text" size="3" name="object-ns" value="<?php echo $_SESSION['object-ns'] ?>"/></td>
                                                                                                </tr>
                                                                                        </table>
                                                                                </td>
                                                                        </tr>
                                                                </table>
                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
                <table width="99%" border="0" cellpadding="0" cellspacing="0" align="center">
                        <tr>
                                <td>Enter your query text or call xml here:<br />
                                        <textarea tabindex="7" rows="10" cols="60" name="body" wrap="virtual"><?php echo ZuoraAPIHelper::xmlspecialchars($_SESSION['body']) ?></textarea><br />
                                        <input tabindex="8" type="submit" value="Submit" name="submit"/><input tabindex="11" type="submit" value="Clear" name="reset"/>
                                        <input tabindex="9" type="checkbox" name="csv">CSV Output</input>&nbsp;
                                        <input tabindex="10" type="checkbox" name="queryMore">Use QueryMore to get all results</input><br />
                                </td>
                                <td>
                                        <table>
                                                <tr>
                                                        <td>API Call Templates:</td>
                                                        <td></td>
                                                </tr>
                                                <tr>
                                                        <td>- Pick an Operation:</td>
                                                        <td><select tabindex="92" onChange="document.getElementById('operation').value = this.value;" name="template-op">
                                                                <option value=""></option>
                                                                <?php
                                                                $names = getOperationListFromWSDL($_SESSION['wsdl'], $debug);
                                                                foreach ($names as $name) {
                                                                      if ($name != "login") {
                                                                    echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
                                                                      }
                                                                }
                                                                ?>
                                                                </select>
                                                        </td>
                                                        <td> Docs: <input tabindex="93" type="button" value="----" Id="operation" onClick="popup(this.value);" />&nbsp;</td>
                                                </tr>
                                                <tr>
                                                        <td>- Pick an Object:</td>
                                                        <td><select tabindex="94" onChange="document.getElementById('object').value = this.value;" name="template-object">
                                                                <option value=""></option>
                                                                <?php
                                                                $names = getObjectListFromWSDL($_SESSION['wsdl'], $debug);
                                                                sort($names);
                                                                foreach ($names as $name) {
                                                                      if ($name != "zObject") {
                                                                    echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
                                                                      }
                                                                }
                                                                ?>
                                                                </select>
                                                        </td>
                                                        <td> Docs: <input tabindex="95" type="button" value="----" Id="object" onClick="popup(this.value);" />&nbsp;</td>
                                                </tr>
                                                <tr>
                                                        <td><input tabindex="96" type="submit" value="Get Template" name="template"/></td>
                                                        <td></td>
                                                </tr>
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
       echo "Location: " . $locationString . "<br>\n";
       //echo "Response:" . ZuoraAPIHelper::$client->myResponse . "\n";
       echo "Request:" . $requestString . "\n";
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
        </tr>
</table>
</body>
</html>