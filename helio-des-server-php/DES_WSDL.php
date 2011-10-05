<?php

/** 
*   @file DES_WSDL.php
*   @brief PHP SoapServer for Helio WebServices
*
*   @version  $Id: $
*   
*/
 
 
  require_once 'DES_ini.php';

  ini_set("soap.wsdl_cache_enabled", "0"); // desactive le cache WSDL
  $server = new SoapServer('../test.wsdl');  
  $server->setClass('DES_WS');
  $server->handle();

?>
