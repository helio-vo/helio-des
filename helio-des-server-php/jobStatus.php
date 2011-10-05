<?php
/** 
*   @file jobStatus.php
*   @brief  DES REST interface to job status services
*
*   @version  $Id:  $
*/

  require_once 'DES_ini.php';

  $des_ws = new DES_WS();

 // header('Content-Type: text/xml');

  switch ($_GET['MODE']) {
    case 'phase':	
	$result = $des_ws->GetStatus($_GET);
	echo '<Status><ID>'.$result["Status"]["ID"].'</ID>'.'<status>'.$result["Status"]["status"].'</status>'.'<description>'.$result["Status"]["description"].'</description></Status>';
      break;
    case 'abort':	
	$result = $des_ws->Abort($_GET);
	echo '<Status><ID>'.$result["Status"]["ID"].'</ID>'.'<status>'.$result["Status"]["status"].'</status>'.'<description>'.$result["Status"]["description"].'</description></Status>';
      break;
    case 'result':	 
	$result = $des_ws->GetResult($_GET);
	echo '<ResultInfo><ID>'.$result["ResultInfo"]["ID"].'</ID>'.'<resultURI>'.$result["ResultInfo"]["resultURI"].'</resultURI>'.'<fileInfo>'.$result["ResultInfo"]["fileInfo"].'</fileInfo> '.'<status>'.$result["ResultInfo"]["status"].'</status> '.'<description>'.$result["ResultInfo"]["description"].'</description></ResultInfo>';
      break;
    default :
          echo '<error> no such service</error>';     
    }

?>
