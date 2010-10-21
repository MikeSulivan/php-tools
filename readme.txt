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
