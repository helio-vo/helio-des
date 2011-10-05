<?php
/** 
*   @file killPID.php 
*   @brief  
*
*   @version $Id:  $ 
*/
    require_once 'DES_ini.php';
 
    $ID = $argv[1];
    $pid = $argv[2];
    $timeOut = $argv[3];

/*  	Predefined TimeOut (in HelioMDESserverWeb_ini.php)
*	Application waits for this time before kill the process  	
*/  
        sleep($timeOut); 
	exec("ps $pid", $ProcessState);

 //  if process is pending, kills it and puts an error message into the temporary error file 	
	if (count($ProcessState) >= 2) {
		  exec("kill -9 $pid");

		  $fp = fopen(errorDir.$ID, 'a');
		  fwrite($fp, "TimeOut");
		  fclose($fp);


 	  }
?>