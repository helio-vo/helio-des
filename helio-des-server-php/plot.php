<?php
  
/** 
*   @file plot.php 
*   @brief  REST interface for Query
*
*   
*   @version $Id:  $ 
*/

  require_once 'DES_ini.php';


  $des_ws = new DES_WS();

  $result = $des_ws->LongTimeQuery($_GET);
 
//  header('Content-Type: text/xml');
  if ($result['ID'] != null)  
       echo '<jobid>'.$result['ID'].'</jobid>';
  elseif ($result['error'] != null)
       echo '<error>'.$result['error'].'</error>';
  else 
       echo '<error> unknown error </error>';
?>


