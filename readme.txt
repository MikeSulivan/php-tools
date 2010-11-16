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

Zuora PHP Toolkit

INTRODUCTION
------------

The PHP toolkit provides useful utilities for Zuora developers to introspect, pull data from and develop
against the Zuora APIs.

REQUIREMENTS
------------

This utility requires PHP with add-ons. Furthermore, the below are required to run this package.

PHP 5.2.9
cURL (for secure requests over HTTPS)
Tidy
SOAP

These others may be required.

OpenSSL
XML-RPC
XSL

CONTENTS
--------

call-lib.php - re-usable library of Zuora API calls
index.php - API utility front page
queryMore.xml - queryMore() template
readme.txt -this file
subscribe.xml - subscribe() template, latest version
subscribeV9.xml- subscribe() template, version 9
subscribeV11.xml - subscribe() template, version 11
subscribeWithExistingAccount.xml - subscribeWithExistingAccount() template, latest version
subscribeWithExistingAccountV9.xml- subscribe() template, version 9
zuora-19.0-sandbox-AllOptions.wsdl - latest WSDL, sandbox

INSTALLATION INSTRUCTIONS
-------------------------

The following instructions assume that you're going to use Xampp (http://www.apachefriends.org/en/xampp.html) on the localhost as the web server.

a) Make sure no other application is running on port 80. You can verify this by running "netstat -a -n -o" on the command prompt. Terminate the offending app, if running on port 80.

b) Install Xampp for Windows using default path.

c) Run the Xampp config tool.

d) Click on Port Check button on the Xamp control panel. Make sure Port 80 is available.

e) In the Xampp control panel start Apache server.

f) In the Xampp directory, extract the zip file (Zuora source) under a folder named api-util.

g) Make sure Apache is running.

h) In a web browser type http://localhost/api-util/. This should load up the API Utility for Zuora.

DOCUMENTATION & SUPPORT
-----------------------

API Documentation is available at http://developer.zuora.com
