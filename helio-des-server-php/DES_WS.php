<?php
/* $Id: DDserverWebHelio.php, v 0.1 2011/02/10 natasha based on file DDserverWeb.php,v 1.4 2008/08/22 14:57:45 elena Exp $ */
/** 
*   @file HelioServerWeb.php
*   @brief PHP SoapServer for Helio WebServices
*
*   @date 14.01.2011
*   @version 0.1 
*/

require_once 'HelioMDESserverWeb_ini.php';

class DES_WS {

    private $user, $passwd, $IP;
    private $chain, $vars;
    private $statusdescription;   
    private $resDirName;



	 function __construct(){
		    
/* ===============================================================================================================
  In AMDA registered 5 logins helio (helio1,helio2, ..., helio5  ) with a password helio.
  In this version only login helio1 used.
  Variables user, passwd & vars are global variables.
  =============================================================================================================== */		    
		    $this->user = 'helio1';
		    $this->passwd = 'helio';	
		    $this->vars = array();
	    }

/**
*  This function return the jobID (used later as variable $ID).
*  It consists of permanent prefix "HQ" and a random integer value between 10000 and 99999.
*
*	@param   void
*	@return  string  
*/
          public function setID(){

                   $HELIOPREFIX = "HQ";
		   $nb_min = 10000;
		   $nb_max = 99999;	      
		   $nombre = mt_rand($nb_min,$nb_max);
		   
		    $this->IP = $this->getIPclient();
		    
	      return $HELIOPREFIX.$nombre;
           }

/**
*  Function getIPclient return the IP client sent a request for needs DD scripts (DD_htmllogin, Check_User, DD_Search)
*
*	@param   void
*	@return  string  
*/ 
	  public  function getIPclient(){
 
	    if (getenv('HTTP_X_FORWARDED_FOR'))
	            $realIP = getenv('HTTP_X_FORWARDED_FOR');
            elseif (getenv('HTTP_CLIENT_IP')) 
		    $realIP = getenv( 'HTTP_CLIENT_IP' );
            else	          
		    $realIP = getenv('REMOTE_ADDR');
	  
	  return $realIP;
	  }    
/**
*  DD LOGIN if helio1 hasn't registerd in AMDA create login for this user.
*
*	@param   void
*	@return  integer 
*/
 /*===============================================================================================================
  Function read contents of /home/budnik/Amda-Helio/DDHTML/cfg/login.cfg
  and launches script DD_htmllogin
  =============================================================================================================== */  
           public function ddLogin() {
             
             $login = file_get_contents(ROOT_PATH."/cfg/login.cfg"); 
             $loginCommd = $login.DDBIN."DD_htmllogin ".$this->user." ".$this->passwd." ".$this->IP."\n";
             system($loginCommd, $res);
             return $res;

	  }


/**
*  DD CHECK if helio1 has already registerd in AMDA and launches script Check_User. 
*
*	@param   void
*	@return  integer 
*/  
	 public function ddCheckUser() {
		
		$cmdCheckUser =  DDBIN."Check_User ".$this->IP." ".$this->user." 1> /dev/null 2> /dev/null";   
		system($cmdCheckUser, $res);
		return $res;
          }
/** 
*  Function makeDir checks the availability of the necessary folders. Creating missing folders. 
*
*	@param   string $ID
*	@return  boolean 
*/
/* ===============================================================================================================
  Destination of folders:
  ==  resultDir (ex. "/home/budnik/Amda-Helio/DDHTML/WebServices/HelioResults/")
    - folder with temporary folders. Temporary folders named jobID, contains TT & RES folders used for dd scripts  

  == errorDir (ex. "/home/budnik/Amda-Helio/DDHTML/WebServices/HelioErrors/")
    - directory is intended to check in possible errors in launching php script   
    - when php script started in background, file named with jobID, is created 

  == finalDir (ex. "/home/budnik/Amda-Helio/DDHTML/WebServices/Results/")
    - destination of final VOtables, URI of these files will be returned in ResultInfo

  == pidDir (ex. "/home/budnik/Amda-Helio/DDHTML/WebServices/HelioPID/")
    - directory with files named jobID. These files contains PID of php process

  == /RES is subfolder of errorDir/[pidID] folder 
    - folder is for files used in starting the ddSearch

  == /TT is subfolder of errorDir/[pidID] folder 
    - folder is designed for intermediate file with the results of ddSearch
 =============================================================================================================== */
	  function makeDir($ID){
	     
             if (!is_dir(resultDir)) mkdir(resultDir, 0755, true);
             if (!is_dir(errorDir)) mkdir(errorDir, 0755, true);
             if (!is_dir(finalDir)) mkdir(finalDir, 0755, true);
             if (!is_dir(pidDir)) mkdir(pidDir, 0755, true);

             $this->resDirName = resultDir.$ID;
             if (mkdir($this->resDirName, 0755, true)) 
	        if (mkdir($this->resDirName."/RES", 0755, true))
	         if (mkdir($this->resDirName."/TT", 0755, true)) return true;

             return false;
	   }
// =============================================================================================================
/** makeRequest reads the conditions received from helio and 
*   prepare a request object.
*
*  Function makeRequest reads the conditions received from helio and 
*  prepare a request object to send the script as a parameter
*  Format of condition: 
*    J[join-optional].mission[optional].function[mandatory].argument[mandatory],value[value of argument in PQL format]
*
* @param     	string 	$conditions, $start, $stop, $from; 
		boolean $oneInterval

* @return    	string    
*/
//  ===============================================================================================================
// 	private function makeRequest($conditions, $start, $stop, $from, $oneInterval) {
//   
// 	    $helioRequest = new stdClass();  
// 	    $index = 0;
// /*  Creation object with data to search results */
// 
//     foreach ($conditions as $condition) {     
// 	  if ($condition != '') {
// 		    $parts =  explode(',',$condition);
// 		    $args =  explode('.',$parts[0]);
// 		    $argsN = count($args);
// 
// 		    if ($argsN > 1) {
// 				  
// 			  $currentFunction = $args[$argsN - 2];
// 			  $currentArg = $args[$argsN - 1];
// 			  if ($argsN > 2) {
// 			      if ($args[$argsN - 3] !== "J")
// 				      $currentMission = $args[$argsN - 3];
// 			      else $currentMission = $from[0];
// 			  }
// 			  else  $currentMission = $from[0];				  
// 		    }       
//   
// 		      if (!$helioRequest->func){			   
// 			    $helioRequest->func = $currentFunction;
// 			    $helioRequest->mission = $currentMission;
// 			    $helioRequest->starttime = $oneInterval ? $start[0] : $start[$index];
// 			    $helioRequest->stoptime = $oneInterval ? $stop[0] : $stop[$index];
// 		      }
// 		      if ($helioRequest->func != $currentFunction || $helioRequest->mission != $currentMission) {
// 
// 	// new request
// 			  $helioRequests[] = $helioRequest;
// 			  $index++;
// 			  $helioRequest = new stdClass();			 
// 			  $helioRequest->func = $currentFunction;
// 			  $helioRequest->mission = $currentMission;
// 			  $helioRequest->starttime = $oneInterval ? $start[0] : $start[$index];
// 			  $helioRequest->stoptime = $oneInterval ? $stop[0] : $stop[$index];
// 		      }
// 		
// 		      $helioRequest->arg[$currentArg] = $this->parseCondition($parts[1]); 
// 	    }		             
// 	  }
// 	
// 	$helioRequests[] = $helioRequest;   
// 	return $helioRequests;
//    }
// 

/**
*	Transform condition from format PQL in available by Amda format
*
* @param     	string $condition
* @return    	string  
*/
  function parseCondition($condition) { 
/* 
*	PQL format		signification 	 AMDA		example
*       ==========		=============	======		=======
*   '/' before number - 	  '>'  		' GT '	 deltav,/500  eq.  deltav > 500  eq. deltav GT 500(AMDA)
*   '/' after number -            '<'  		' LT '	 deltav,500/  eq.  deltav < 500  eq. deltav LT 500(AMDA)
*   no '/' 			  '='		nothing	 deltav,500   eq.  deltav = 500  eq. deltav 500(AMDA)
*   '/' between number			  GT $smallCond, LT greatCond	
*						  deltav,500/600 eq.    500<deltav<600   eq. deltav GT 500, deltav LT 600
*/	
		$length = strlen($condition);
		if (strpos($condition, '/') === 0) $condArr = ' GT '.substr($condition,1); 	//$condArr = array('GT',substr($condition,1));
		if (strpos($condition, '/') === false) $condArr = $condition; 		//= array('EQ',$condition); 
		if (strpos($condition, '/') == $length-1) $condArr = ' LT '.substr($condition,0,$length-1); //  array('LT',substr($condition,0,$length-1));
                if (strpos($condition, '/') > 0 &&  strpos($condition, '/') < $length-1) {
				     $smallCond = explode('/',$condition);
                                     $condArr = array(' GT '.$smallCond[0], ' LT '.$smallCond[1]);
		 }

       return $condArr;
  }

/**
*         Remove all temporary Dir (HelioResult, HelioError, HelioPID)
*
* @param     	string $dir
* @return    	void  
*/
    function rrmdir($dir){
      if (is_dir($dir)) {
	$objects = scandir($dir);
// Recursively delete a directory that is not empty and directorys in directory
	foreach ($objects as $object) {
// If object isn't a directory recall recursively this function 
	  if ($object != "." && $object != "..") {
	    if (filetype($dir."/".$object) == "dir") 
                     $this->rrmdir($dir."/".$object);
            else 
                    unlink($dir."/".$object);
	  }
	}
	reset($objects);
	rmdir($dir);
      }
    } 

/**
* Run Application in background
*
* @param     	string $Command, $ID
* @return    	string  
*/
    function background($Command, $ID){

      $PID = exec("$Command 1> /dev/null 2> ".errorDir.$ID." & echo $!");
      if($PID!="")  return $PID;
      

      return false; 
   }

/**
* Check if the Application running and return string in xml format with status!
*
* @param     	string $PID
* @return    	string
*/
   function is_running($PID){

	exec("ps $PID", $ProcessState);
	       
	if (count($ProcessState) >= 2) {
		  $status = 'PENDING';
		  $this->statusdescription = 'query pending';
        }
	else {
 		  $status = 'COMPLETED';
		  $this->statusdescription = 'query completed';
 	  }
		
       return $status;

   }

////////////////////////////////////////         MAKE DATA         //////////////////////////////////////////////
// =============================================================================================================
/** makeRequest reads the conditions received from helio and 
*   prepare a request object.
*
*  Function makeRequest reads the conditions received from helio and 
*  prepare a request object to send the script as a parameter
*  Format of condition: 
*    J[join-optional].mission[optional].function[mandatory].argument[mandatory],value[value of argument in PQL format]
*
* @param     	string 	$conditions, $start, $stop, $from; 
		boolean $oneInterval

* @return    	string    
*/
//  ===============================================================================================================
	private function makeRequest($conditions, $start, $stop, $from, $oneInterval) {
  
	    $helioRequest = new stdClass();  
	    $index = 0;
/*  Creation object with data to search results */

    foreach ($conditions as $condition) {     
	  if ($condition != '') {
		    $parts =  explode(',',$condition);
		    $args =  explode('.',$parts[0]);
		    $argsN = count($args);

		    if ($argsN > 1) {
				  
			  $currentFunction = $args[$argsN - 2];
			  $currentArg = $args[$argsN - 1];
			  if ($argsN > 2) {
			      if ($args[$argsN - 3] !== "J")
				      $currentMission = $args[$argsN - 3];
			      else $currentMission = $from[0];
			  }
			  else  $currentMission = $from[0];				  
		    }       
  
		      if (!$helioRequest->func){			   
			    $helioRequest->func = $currentFunction;
			    $helioRequest->mission = $currentMission;
			    $helioRequest->starttime = $oneInterval ? $start[0] : $start[$index];
			    $helioRequest->stoptime = $oneInterval ? $stop[0] : $stop[$index];
		      }
		      if ($helioRequest->func != $currentFunction || $helioRequest->mission != $currentMission) {

	// new request
			  $helioRequests[] = $helioRequest;
			  $index++;
			  $helioRequest = new stdClass();			 
			  $helioRequest->func = $currentFunction;
			  $helioRequest->mission = $currentMission;
			  $helioRequest->starttime = $oneInterval ? $start[0] : $start[$index];
			  $helioRequest->stoptime = $oneInterval ? $stop[0] : $stop[$index];
		      }
		
		      $helioRequest->arg[$currentArg] = $this->parseCondition($parts[1]); 
	    }		             
	  }
	
	$helioRequests[] = $helioRequest;   
	return $helioRequests;
   }


////////////////////////////////////////         REQUESTS          //////////////////////////////////////////////
/**
*           MAIN LONG RUNNING QUERY
*
*   Receive request from helio. 
*   Translate data in Amda available formats. 
*   Start the php process in background. 
*
* @param     	list $data
* @return    	string  
*/
   
	 function  LongQuery($start, $stop, $missions,$where) {
/* Generation random jobID (function setID())*/
	    $ID = $this->setID();
 
/* Call a function getIPclient() to find client IP and put it in global variable  */ 
            $this->IP = $this->getIPclient();
/* DD CHECK if HELIO1 has already registerd; if not  DD LOGIN */  
           if ($this->ddCheckUser() != 0) {        
	          if ($this->ddLogin() != 0) 
//                            throw new SoapFault("server00","Server Error: DD Login");  	TODO error message
		      echo 'error';
	          if ($this->ddCheckUser() != 0)  
// 			      throw new SoapFault("server01","Server Error: Check User");	TODO error message
		      echo 'error';
           }
/* Make all using in this service folders */		 
            if (!$this->makeDir($ID)) throw new SoapFault("server02","Server Error: Mkdir");

/* Testing if time intervals are multiples */	  
	    $oneInterval = false;    
	    if (count($start) == 1) $oneInterval = true;	    
	    $conditions = explode(';',$where);	
/*  Control number of missions and if missions name is present in the WHERE	*/
/*  if WHERE hasn't missions, dublicate conditions whith all misssions	 	*/
	    $whereParts = explode(',',$conditions[0]);
	    $firstPart = explode('.',$whereParts[0]);
	    if ((count($missions) > 1) && (count($firstPart) < 3)){
	      foreach ($missions as $mission){
		foreach ($conditions as $condition){
		  $condition = $mission.'.'.$condition;
		  $newConditions[] = $condition;
		}
	      }
	    $conditions = $newConditions;
	    }
/* Call function makeRequest() to complete set of arguments to pass through a script php */
	    $requests = $this -> makeRequest($conditions, $start, $stop, $missions, $oneInterval);
/* Complete set of arguments with IP client & jobID */	    
	    $helioRequests = new stdClass();  
	    $helioRequests->requests = $requests;
	    $helioRequests->IP = $this->IP;
	    $helioRequests->ID = $ID;
     
/* Encoding a serialized string of stdClass() because serialized string has 
   special characters generated after serialization.
*/
	    $argument = urlencode(serialize($helioRequests));
/* Preparation command and lunch php script multiRequest.php in background with argument
*/
	    $cmd = 'php '.wsDir.'/helio-des/multiRequest_new.php '.$argument;		//TODO suppr /TEST_NAT2
	    $pid =  $this->background($cmd, $ID);
/* Save PID of this script in the folder: ex. /home/budnik/Amda-Helio/DDHTML/WebServices/HelioPID
   file named with jobID ($ID)
*/
	    if ($pid) {
                $filename = pidDir.$ID;
	        $file = fopen($filename,"w");
	        fwrite($file,$pid);
	        fclose($file); 
		$cmdTimeOut = 'php '.wsDir.'/killPID.php '.$ID.' '.$pid;
		$pidTimeOut =  $this->background($cmdTimeOut, $ID);
             }
             else 
                   throw new SoapFault("server03","Server Error: Can't launch process");
// Launch script with TimeOut 
	    return array('ID' => $ID);
	  }
/**
*          GET STATUS
*
* Recive as argument jobID in array format 
* Checks process (by PID).
* If the process is completed, checks the files of the results.
*
* @param     	list $data
* @return    	string  LongQuery
*/ 
       function GetStatus($ID) {

	    $filename = pidDir.$ID;
/*Checks whether the file with process PID is created, if not - error message*/
	    if (!file_exists($filename))               
 	       return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no process ID')); 
          
// Read pid from file in variable $pid and return string in xml format with status
	    $fileContents = file_get_contents($filename);
	    $pid = $fileContents;   
// Testing if php process running yet 
	    $status = $this->is_running($pid);

	    if ($status == 'COMPLETED') {		  
		$dirName = resultDir.$ID."/TT/";
		$xmlName = "helioResult";
// Testing generating files
		if (!file_exists($dirName.$xmlName.'.xml'))  
		      return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no result table'));
	
                if (!rename($dirName.'VOT.xml',finalDir.'MDES_'.$ID.'.xml') && !file_exists(finalDir.'MDES_'.$ID.'.xml'))
                       return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'problems while copying VOT'));  
                      	 			 
	  }
// Result array according to WSDL requirements          
	  $ResultInfo = array('Status' => array('ID' => $ID, 'status' => $status,'description' =>   $this->statusdescription)); 

 	  return $ResultInfo;
       }

/**
*    GET RESULTS
*
* @param     	list $data
* @return    	string  
*/ 
       function GetResult($ID) {
	    
	    $filePID = pidDir.$ID;
	    $fileError = errorDir.$ID;
// Taking information about errors during the script. If file is empty - no errors.
 	    $fileInfo = file_get_contents($fileError);
// Name of result file
 //           $votName = 'VOT'.$ID.'.xml'; 
	    $votName = 'MDES_'.$ID.'.xml'; 

 	    $resultURI = webAlias. $votName;	//REMETTRE   TODO
      
// Result array according to WSDL requirements  
	    $ResultInfo = array('ResultInfo' =>
                          array('ID' => $ID,'resultURI' => $resultURI,'fileInfo' => $fileInfo,'status' => 'COMPLETED','description' => 'Data Is Ready')); 

// Delete files with ended PID and Errors	    
	    if (file_exists($filePID)) unlink($filePID);
	    if (file_exists($fileError)) unlink($fileError);
// Deleting temporary folders
	    $this->rrmdir(resultDir.$ID);
 	  return $ResultInfo;
       }


}

?>


