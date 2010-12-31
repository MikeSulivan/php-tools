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