<?php
    require_once 'HelioMDESserverWeb_ini.php';
// Reception PID and jobID
    $ID = $argv[1];
    $pid = $argv[2];
/*  	Predefined TimeOut.
*	Application waiting this time before killing process 
*  	Modification of TimeOut need be in the sleep
*/  
    sleep(TIMEOUT);
 // start killing process   
	exec("ps $pid", $ProcessState);
 //  if a process works, kill it and put a error message in the temporary error file 	
	if (count($ProcessState) >= 2) {
		  exec("kill -9 $pid");
		  $fp = fopen(errorDir.$ID, 'w');
		  fwrite($fp, "TimeOut");
		  fclose($fp);
 	  }
?>