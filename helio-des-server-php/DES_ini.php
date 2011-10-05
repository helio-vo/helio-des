<?php

/**
*  @file DES_ini.php
*  @version $Id: $
*  @brief All necessary SETUP for helio-des WebServices
*
*/


/* MAIN DEFINITIONS SHOULD BE MODIFIED!!!!!! */

  define("Verbose", true);

  date_default_timezone_set('UTC');

  
  define( "ROOT_PATH","/home/budnik/Amda-Helio/DDHTML/");

  define("webAlias", "http://manunja.cesr.fr/MDES/");
  define("DDLIBHOME","/home/budnik/AMDANEW/DDLIB/");
  define("AMDALIBHOME","/home/budnik/AMDANEW/AMDALIB/");
  define("TIMEOUT", 600);

/****************** Compound Definitions *********************/

  define("wsDir", ROOT_PATH."/WebServices/");
  define("DDBIN",DDLIBHOME."/bin/");
  define("pidDir", wsDir."/HelioPID/");
  define("errorDir", wsDir."/HelioError/");
  define("resultDir", wsDir."/HelioResult/");
  define("finalDir", wsDir."/Results/");
  define("expressionDir", ROOT_PATH."/REQ/");
  define("xmlDir", wsDir."/helio-des/XML/");

// XML files with descriptions and DD requests
    define("functionsXml", xmlDir."DesFunctions.xml"); 
    define("amdaParametersXml", xmlDir."AmdaParameters.xml");
    define("plotsXml", xmlDir."DesPlots.xml"); 

    define("searchList", "search.list");
    define("searchRes", "search.res");
    define("requestList", "request.list");


  if (Verbose) define("log",fopen(wsDir."/log","w"));

// PHP CLASS PATH
  define("CLASSPATH",ROOT_PATH."/HTML");
  define("XML_BASE_DIR",ROOT_PATH."/XML/");
  
// DD environment
 define("DDLIB", DDLIBHOME."/lib");
 define("DDPROJECT","AMDA");
 define("DDPROJLIB",AMDALIBHOME."/lib/");

 
  set_include_path(".:".CLASSPATH);
  
  if (!function_exists('__autoload')) {
     function __autoload($class_name) {
           require_once $class_name . '.php';
     } 
 }
?>
