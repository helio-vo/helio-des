<?

/**
*  @file DDserverWeb_ini.php
*  @version $Id: DDserverWeb_ini.ryba,v 1.3 2009/01/14 11:10:11 budnik Exp $
*  @brief All necessary SETUP for DD WebServices
*
*/



/* MAIN DEFINITIONS SHOULD BE MODIFIED!!!!!! */

//////////////////////////////////////////////////////// 
  define( "ROOT_PATH","/home/budnik/Amda-Helio/DDHTML");

 // define("rootDir", "/data/DDBASE");
  define("webAlias", "http://manunja.cesr.fr/MDES/");
  define("DDLIBHOME","/home/budnik/AMDANEW/DDLIB");
  define("AMDALIBHOME","/home/budnik/AMDANEW/AMDALIB");
  define("TIMEOUT", 600);

/****************** Compound Definitions *********************/

 // define("wsDir", ROOT_PATH."/WebServices");
  define("wsDir", ROOT_PATH."/WebServices");
  define("DDBIN",DDLIBHOME."/bin/");
  define("pidDir", wsDir."/HelioPID/");
  define("errorDir", wsDir."/HelioError/");
  define("resultDir", wsDir."/HelioResult/");
  define("finalDir", wsDir."/Results/");
  define("expressionDir", ROOT_PATH."/REQ/");
  define("xmlDir", wsDir."/XML/");

//define("log",fopen(wsDir."/log","w"));

// PHP CLASS PATH
  define("CLASSPATH",ROOT_PATH."/HTML");
  define("XML_BASE_DIR",ROOT_PATH."/XML");

 // define("SoapServer",wsDir."HelioLongQueryService_MDES.wsdl");

//  define("baseDir", rootDir."/DATA/");
//  define("extBaseDir",rootDir."/INFO/");
//  define("extBaseXml", "Bases.xml");
     
//  define("webAlias", rootAlias."/DATA/");
//  define("extWebAlias", rootAlias."/INFO/");
//  define("DDBASEBIN", DDLIBHOME."/bin");
  

// define("exportLIBS","export LD_LIBRARY_PATH=/usr/lib:/usr/local/lib:".DDLIB."; ");

// DD environment
 define("DDLIB", DDLIBHOME."/lib");
 define("DDPROJECT","AMDA");
 define("DDPROJLIB",AMDALIBHOME."/lib/");

// Errors 
 // define("OUTOFTIME","OUTOFTIME");
 // define("NODATASET","NODATASET");
//  define("NOEXTERNALBASES","NOEXTERNALBASES");
//  define("NOEXTERNALDATA","NOEXTERNALDATA");
//  define("NOLOCALDATA","NOLOCALDATA");
//  define("NOUSERGROUPSSPECIFIED","NOUSERGROUPSSPECIFIED");
 set_include_path(".:".CLASSPATH);

 if (!function_exists('__autoload')) {
     function __autoload($class_name) {
            require_once $class_name . '.php';
     }
 }
?>
