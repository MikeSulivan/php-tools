<?php
include("../call-lib.php");
include("settings.php");

$debug = 0;

session_start();

// Test whether the form has been submitted.
if (array_key_exists('_submit_check', $_POST)) {
   $_SESSION['method'] = $_POST['method']; // Create/Update/Subscribe/SWEA/Delete
   $_SESSION['username'] = $_POST['username'];
   $_SESSION['password'] = $_POST['password'];
   $_SESSION['wsdl'] = $_POST['wsdl'];
   $_SESSION['inputfile'] = cleanUpXML($_POST['inputfile']); // Input file name.
   $_SESSION['template-op'] = $_POST['template-op'];
   $_SESSION['template-object'] = $_POST['template-object'];

   // Nuke the session if the Clear button was pressed.
   if (isset($_POST['reset'])) {
      unset($_SESSION['method']);
      unset($_SESSION['username']);
      unset($_SESSION['password']);
      unset($_SESSION['wsdl']);
      unset($_SESSION['inputfile']);
      unset($_SESSION['template-op']);
      unset($_SESSION['template-object']);
   }
}

// Initialize session if we're starting anew.
if (!isset($_SESSION['method'])) {
   $_SESSION['method'] = "";
}
if (!isset($_SESSION['username'])) {
   $_SESSION['username'] = "";
}
if (!isset($_SESSION['password'])) {
   $_SESSION['password'] = "";
}
if (!isset($_SESSION['wsdl'])) {
   $_SESSION['wsdl'] = $DEFAULT_WSDL;
}
if (!isset($_SESSION['inputfile'])) {
   $_SESSION['inputfile'] = "";
}
if (!isset($_SESSION['template-op'])) {
   $_SESSION['template-op'] = "";
}
if (!isset($_SESSION['template-object'])) {
   $_SESSION['template-object'] = "";
}

#################################
function execInBackground($cmd) {
    if (substr(php_uname(), 0, 7) == "Windows"){
        //pclose(popen("start /B ". $cmd, "r"));
	exec($cmd);
    } else {
        exec($cmd . " > /dev/null &");
    }
}

#################################
function cleanUpXML($xml) {
   $retVal = $xml;
   $retVal = str_replace("\\\"", "\"", $retVal);
   $retVal = str_replace("\\'", "'", $retVal);
   return $retVal;
}

#################################
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
?>

<html>
 <head>
  <title>Z-Loader Utility</title>
 </head>
 <body>
  <p><b>Function:</b> This utility allows mass API operations (Create, Update, Delete) driven from a CSV file. Each row of the CSV file represents an object and the column headers should match the API object fields exactly (the match is case sensitive). List of object fields can be found in the <a href="http://apidocs.developer.zuora.com/index.php/Main_Page">API documentation</a>. More help and usage examples for this utility can be found on this <a href="https://sites.google.com/a/zuora.com/services/Home/api-support">Wiki page</a>.</p>
  <form enctype="multipart/form-data" method="POST" action=".">
   <input type="hidden" name="_submit_check" value="1"/>

<table>
<tr>
<td>WSDL:</td><td><select name="wsdl">
<?php
$wsdl_files = dirList($baseDir);

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
</select></td>
<td>SOAP&nbsp;Location:</td><td><?php
echo ZuoraAPIHelper::getSoapAddress($baseDir . $_SESSION['wsdl']);
?></td>
</tr>

<tr>
<td>Username:</td><td><input type="text" size="30" name="username" value="<?php echo $_SESSION['username'] ?>"/></td>
<td>Pick an Operation:</td><td><select name="template-op">
<option value=""></option>
<?php
$names = getOperationListFromWSDL($baseDir . $_SESSION['wsdl'], $debug);
foreach ($names as $name) {
      if ($name != "login") {
      	 if (strcasecmp($name,$_SESSION['template-op']) == 0)
	    echo "<option value=\"" . $name . "\" selected=\"yes\">" . $name . "</option>\n";
   	 else
	    echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
      }
}
?>
</select></td>
</tr>

<tr>
<td>Password:</td><td><input type="password" size="30" name="password" value="<?php echo $_SESSION['password'] ?>"/></td>
<td>Pick an Object:</td><td><select name="template-object">
<option value=""></option>
<?php
$names = getObjectListFromWSDL($baseDir . $_SESSION['wsdl'], $debug);
foreach ($names as $name) {
      if ($name != "zObject") {
      	 if (strcasecmp($name,$_SESSION['template-object']) == 0)
	    echo "<option value=\"" . $name . "\" selected=\"yes\">" . $name . "</option>\n";
   	 else
	    echo "<option value=\"" . $name . "\">" . $name . "</option>\n";
      }
}
?>
</select></td>
</tr>

<tr>
<td>Input File:</td><td><input name="inputfile" type="file" /></td><td>&nbsp;</td><td>&nbsp;</td>
</table>

<input type="submit" value="Submit" name="submit"/><input type="submit" value="Clear" name="reset"/><input type="submit" value="Refresh" name="refresh"/>&nbsp;<input type="submit" value="Clear Log Files" name="clear"/>
<input type="checkbox" name="show-request">Show request</input><br />

<?php
// Do the actual work.
if (isset($_POST['clear'])) {
    try {
        fclose(fopen($OUTPUT_FILE, "w"));
        fclose(fopen($LOG_FILE, "w"));
    } catch (Exception $e) {
        echo "Warning: Log files could not be deleted.<br/>\n";
    }
}

if (isset($_POST['submit'])) {
    // get and use remaining arguments
    $method = $_SESSION['method'];
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
    $wsdl = $_SESSION['wsdl'];
    $operation = $_POST['template-op'];
    $object = $_POST['template-object'];
    $showRequest = "false";
    if ($_POST['show-request']) {
        $showRequest = "true";
    }
    $fileName = $_FILES['inputfile']['tmp_name'];
    try {
        // Shift uploaded file to input file.
	$if = fopen($INPUT_FILE, "w");
	$content = ZuoraAPIHelper::getFileContents($fileName);
	fwrite($if, $content);
	fclose($if);

    	// Start processing
    	$command = "php call.php " 
    		     . escapeshellarg($baseDir . $wsdl) . " " 
    		     . escapeshellarg($username) . " " 
    		     . escapeshellarg($password) . " "
    		     . escapeshellarg($operation) . " "
    		     . escapeshellarg($object) . " "
    		     . escapeshellarg($showRequest);
    	unset($output);
    	execInBackground($command);
    	//exec($command, $output, $returnVal);
        sleep(5);
    } catch (Exception $e) {
        echo "<p><b>Error:</b> couldn't upload file: " . $e . "<p/>\n";
    }
}
?>
  </form>

  <table border="1">
<tr>
<?php
foreach ($OUTPUT_HEADER as $header) {
    echo "<td><b>" . $header . "</b></td>";
}
echo "\n";
?>
</tr>
<?php
// Print out the log file.

try {
    $log = ZuoraAPIHelper::getFileContents($OUTPUT_FILE);
    $log_array = explode("\n", $log);
    foreach ($log_array as $line) {
        $columns = explode($SEPARATOR, $line);
	if (count($columns) > 1) {
            echo "<tr>";
            foreach ($columns as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>\n";
        }
    }
} catch (Exception $e) {

}
?>
  </table>
<p>Log File:<br/>
<pre>
<?php
echo ZuoraAPIHelper::getFileContents($LOG_FILE);
?>
</pre>
</p>
 </body>
</html>
