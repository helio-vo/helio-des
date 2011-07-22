<?php
/* $Id: LongRunningQueryService.php, v 0.1 2011/07/12 natasha */
/** 
*   @file LongRunningQueryService.php
*   @brief Long Running Query Rest Service for Helio WebServices
*
*   @date 14.01.2011
*   @version 0.1 
*/

  require_once 'HelioMDESserverWeb_ini.php';


  $des_ws = new DES_WS();
  $MODE = '';
  if (urldecode($_GET['MODE']))
	$MODE 	= urldecode($_GET['MODE']);

  switch ($MODE) {
    case 'phase':
	$ID 	= urldecode($_GET['ID']);
	$result = $des_ws->GetStatus($ID);
	echo '<Status><ID>'.$result["Status"]["ID"].'</ID> '.'<status>'.$result["Status"]["status"].'</status> '.'<description>'.$result["Status"]["description"].'</description></Status>';
      break;
    case 'result':
	$ID 	= urldecode($_GET['ID']);
	$result = $des_ws->GetResult($ID);
	echo '<ResultInfo><ID>'.$result["ResultInfo"]["ID"].'</ID> '.'<resultURI>'.$result["ResultInfo"]["resultURI"].'</resultURI> '.'<fileInfo>'.$result["ResultInfo"]["fileInfo"].'</fileInfo> '.'<status>'.$result["ResultInfo"]["status"].'</status> '.'<description>'.$result["ResultInfo"]["description"].'</description></ResultInfo>';
      break;
    default:
	$start 	= explode(',', $_GET['STARTTIME']);
	$stop 	= explode(',', $_GET['ENDTIME']);
	$missions 	= explode(",", $_GET['FROM']);
	$where 	= $_GET['WHERE'];
	$result = $des_ws->LongQuery($start, $stop, $missions,$where);
	echo '<ID>'.$result['ID'].'</ID>';
      break;
    }
?>
