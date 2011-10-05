<?php
 
/** 
*   @class DES_WS
*   @brief  
*   
*   @version $Id:  $ 
*   Query Syntax: WHERE = function_name,mission_name:parameter_name:arg1_value_condition:arg2_value_condition:...;
*/


class DES_WS {
    
    private $user, $passwd, $IP;
    private $chain, $vars;
    private $statusdescription;   
    private $resDirName, $HELIOPREFIX = 'HQ';
    private $start, $stop, $missions, $nInts;
    private $isSoap = false;

	 function __construct(){
		    
/* ===============================================================================================================
  In AMDA registered 5 logins helio (helio1,helio2, ..., helio5  ) with a password helio.
  In this version only login helio1 used 
  =============================================================================================================== */		    
		    $this->user = 'helio1';
		    $this->passwd = 'helio';
	    }

/**
*  This function return the jobID (used later as variable $ID).
*  It consists of permanent prefix "HQ" and a random integer value between 10000 and 99999.
*
*	@param   void
*	@return  string  
*/
          private function setID(){

		   $nb_min = 10000;
		   $nb_max = 99999;	      
		   $nombre = mt_rand($nb_min,$nb_max);
		   
		    $this->IP = $this->getIPclient();
		    
	      return $this->HELIOPREFIX.$nombre;
           }

/**
*  Function getIPclient return the IP client sent a request for needs DD scripts (DD_htmllogin, Check_User, DD_Search)
*
*	@param   void
*	@return  string  
*/ 
	  private  function getIPclient(){
 
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
          private function ddLogin() {
             
             $login = file_get_contents(ROOT_PATH."/cfg/login.cfg"); 
             $loginCommd = $login.DDBIN."DD_htmllogin ".$this->user." ".$this->passwd." ".$this->IP.PHP_EOL;
             system($loginCommd, $res);
             return $res;

	  }


/**
*  DD CHECK if helio1 has already registerd in AMDA and launches script Check_User. 
*
*	@param   void
*	@return  integer 
*/  
	 private function ddCheckUser() {
		
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
	  private function makeDir($ID, $isPlot){
	     
             if (!is_dir(resultDir)) mkdir(resultDir, 0755, true);
             if (!is_dir(errorDir)) mkdir(errorDir, 0755, true);
             if (!is_dir(finalDir)) mkdir(finalDir, 0755, true);
             if (!is_dir(pidDir)) mkdir(pidDir, 0755, true);

             $this->resDirName = resultDir.$ID;
              if (mkdir($this->resDirName, 0755, true)) 
	        if (mkdir($this->resDirName."/RES", 0755, true)) {
		    if (!$isPlot) { 
			if (mkdir($this->resDirName."/TT", 0755, true)) 
			    return true;
			}
		    else  
			   return true;
		}

             return false;
	   }
 
/**
*	Transform condition from format PQL in available by Amda format
*
* @param     	string $condition
* @return    	string  
*/
	private function parseCondition($condition) { 
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
		if (strpos($condition, '/') === 0) $condArr = ' GT '.substr($condition,1); 	                //$condArr = array('GT',substr($condition,1));
		if (strpos($condition, '/') === false) $condArr = $condition; 		                        //= array('EQ',$condition); 
		if (strpos($condition, '/') == $length-1) $condArr = ' LT '.substr($condition,0,$length-1);     //  array('LT',substr($condition,0,$length-1));
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
    private function rrmdir($dir){
      if (is_dir($dir)) {
	$objects = scandir($dir);
 
	foreach ($objects as $object) { // Recursively delete a directory that is not empty and directorys in directory 
	  if ($object != "." && $object != "..") {  // If object isn't a directory recall recursively this function 
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
    private function background($Command, $ID){

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
   private function is_running($PID){

	exec("ps $PID", $ProcessState);
	       
	if (count($ProcessState) >= 2) {
		  $status = 'EXECUTING';
		  $this->statusdescription = 'query executing';
        }
	else {
 		  $status = 'COMPLETED';
		  $this->statusdescription = 'query completed';
 	  }
		
       return $status;

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
	private function makeRequest($conditions, $start, $stop) {
  
            $oneInterval = count($start) == 1;
	
	    if (!file_exists(functionsXml)) {  // DES functions description
		 //error  "InternalError00: no functions description file"); 
		       
		//return;
	    }
 
	    $dom = new DomDocument("1.0");
	    $dom->load(functionsXml); 
	    $index = 0;

/*  Create object for search request[s] */

	    foreach ($conditions as $condition) {     
		  if ($condition != '') {
			  
			    $parts =  explode(',',$condition); 
			    $func = trim($parts[0]);
 		             
			    $thisFunction =  $dom->getElementById($func);
			    if ($thisFunction == null){
// 				if ($this->isSoap) throw new SoapFault("InternalError01","function $func is not implemented in AMDA-HELIO yet");
// 				else return array("error" => "InternalError01: function $func is not implemented in AMDA-HELIO yet");
// 				return;
			    }	
			    $params =  explode(':',$parts[1]);
			    $mission = trim($params[0]);
 
// find predefined arguments in functions.xml file
			    $args = $thisFunction->getElementsByTagName('arg');
			    $n_param = $thisFunction->getAttribute('n_param');
			    $totalArgs = $args->length + $n_param;
 
			    if ($totalArgs  != (count($params) - 1)) {
// 			    	if ($this->isSoap) throw new SoapFault("InternalError02","function $func : arguments number is ".count($function->arg).
// 						  " expected number is ".$args->length));
// 				else return array("error" => "function $func : arguments number is ".count($function->arg).
// 						  " expected number is ".$args->length));
//  				return;
			    }
// CHECK if parameters are really applicable to the function
 
			    
				  $helioRequest = new stdClass();	 
				  $helioRequest->func = $func;
				  $helioRequest->mission = $mission;
                                 
				  $helioRequest->starttime = $oneInterval ? $start[0] : $start[$index];
				  $helioRequest->stoptime = $oneInterval ? $stop[0] : $stop[$index];
			 
				   for ($i = 0; $i < $n_param; $i++) $helioRequest->parameter[] = $params[$i + 1];

				   $j = 0;
				   foreach($args as $arg) {					    
					    $helioRequest->arg[$arg->getAttribute('name')] = $this->parseCondition($params[$n_param + $j +1]); 
					    $j++;
				  } 

			$index++;
			$helioRequests[] = $helioRequest;  

		  }
	}
		  
		return $helioRequests;
   }

/**
*        Checking Arguments
*/

	  private function checkArgs($vars, $isPlot) {
  	    
	    if ($vars['STARTTIME'] == null) {// STARTTIME is mandatory parameter
			 if ($this->isSoap) throw new SoapFault("request01","Request Error: No StartTime "); //TODO => Global Mission Start would be used");
	                 else return array("error" => "Request Error: No Start Time");		  
	      }
	    if ($vars['ENDTIME'] == null) { // STOPTIME is mandatory parameter
			if ($this->isSoap) throw new SoapFault("request02","Request Error: No End Time");  //TODO => Global Mission End would be used");
			else return array("error" => "Request Error: No End Time");
	    }
                   
	     if ($isPlot) {
		if ($vars['FROM'] == null) { // FROM (== MISSIONS) is mandatory parameter for PLOT
			    if ($this->isSoap) throw new SoapFault("request03","Request Error: No FROM statement"); 
			    else return array("error" => "Request Error: No FROM statement");
			 } 
                        $this->missions = explode(",",$vars['FROM']);  // MISSIONS split into  array  
		}
             else  {		
		  if ($vars['WHERE'] == null) { // WHERE is mandatory parameter for SEARCH
			  if ($this->isSoap) throw new SoapFault("request04","Request Error: No WHERE statement"); 
			    else return array("error" => "Request Error: No WHERE statement");
/* Get simple conditions */ 
                   }  
		      $this->missions = explode(';',$vars['WHERE']); 	    
               }
  
	    $this->start =  explode(',', $vars['STARTTIME']); // STARTTIME split into  array   
	    $this->stop = explode(',',$vars['ENDTIME']);      // ENDTIME split into  array  

            if (count($this->start) != count($this->stop)) {
			if ($this->isSoap) throw new SoapFault("request05","Request Error: The number of StartTimes and EndTimes is different");
			else return array("error" => "Request Error: The number of StartTimes and EndTimes is different");
	    }
           
/*  
*       Control of number of missions/conditions and number of intervals
*                         Two possibilities:  one time interval 		- several missions/conditions
*		                              several time intervals		- several missions/conditions (Number Of Missions/Conditions = Number Of Intervals)
*/ 
	    $this->nInts = count($this->start); // Number of Intervals

	    if ($this->nInts != count($this->missions) && $this->nInts > 1) {
		      if ($this->isSoap) throw new SoapFault("request06","Request Error: The number of StartTime and Missions is different");
		      else return array("error" => "Request Error: The number of StartTime and Missions is different");
     	    }

	    for ($i = 0; $i < $this->nInts; $i++)
		if ($this->start[$i] > $this->stop[$i]) { // StartTime should be less than StopTime
		      if ($this->isSoap) throw new SoapFault("request07","Request Error: StartTime is less than StopTime in ".$i." interval");
		      else return array("error" => "Request Error: StartTime is less than StopTime in ".$i." interval");
	        }
	
           return;
  }
/*****************************************************************
*                           PUBLIC FUNCTIONS
*****************************************************************/ 
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
   
	 public function  LongQuery($data) {
	    if (Verbose) {
		 fwrite(log,'start LongQuery'.PHP_EOL);
	    }
// 	    exit();
            if(is_object($data)) {
		      $vars = get_object_vars($data);
		      $this->isSoap = true;
             }
	    else $vars = $data;
	    
	    if (($result = $this->checkArgs($vars, false)) != null) return $result; // return in case of errors for REST interface
                   
	    $ID = $this->setID();  // unique JobID
            $this->IP = $this->getIPclient(); // Get ClientIP
 
	    if ($this->ddCheckUser() != 0) {        
		    if ($this->ddLogin() != 0) { // DD Login if user is not registered
			 if ($this->isSoap) throw new SoapFault("server00","Server Error: AMDA Login procedure failed"); 
                         else return array("error" => "Server Error: AMDA Login procedure failed");
                     } 	 

		    if ($this->ddCheckUser() != 0) { 
			if ($this->isSoap) throw new SoapFault("server01","Server Error: Check User procedure failed");
			else return array("error" => "Server Error: CheckUser procedure failed");
		    }
	    }
    /* Call function makeRequest() to complete set of arguments to pass through a script php */
	    $requests = $this->makeRequest($this->missions, $this->start, $this->stop); 
//TODO add if is errors in requests
      
            if (!$this->makeDir($ID, false)) { // Make all needed dirs
		 if ($this->isSoap) throw new SoapFault("server02","Server Error: Can't make needed dirs"); 
		 else return array("error" => "Server Error: Can't make needed dirs");
	    }
 
/* Complete set of arguments with IP client & jobID */	    
	    $helioRequests = new stdClass();  
	    $helioRequests->requests = $requests;
	    $helioRequests->IP = $this->IP;
	    $helioRequests->ID = $ID;
            $helioRequests->user = $this->user;

   
	    if (Verbose) {
		  $rr = print_r($helioRequests,true);
		  fwrite(log,$rr.PHP_EOL);
	    }
//    	    exit();	

	    $argument = urlencode(serialize($helioRequests));

/* Launch php script multiRequest.php in background with argument */
 
	    $cmd = 'php '.wsDir.'/helio-des/multiRequest.php '.$argument; 
	    $pid =  $this->background($cmd, $ID);
//  	    exit();	
/* Save PID in the folder: ex. /home/budnik/Amda-Helio/DDHTML/WebServices/HelioPID
   file named by jobID ($ID)
*/
	    if ($pid) {
                $filename = pidDir.$ID;
	        $file = fopen($filename,"w");
	        fwrite($file,$pid);
	        fclose($file);
 
		$cmdTimeOut = 'php '.wsDir.'/helio-des/killPID.php '.$ID.' '.$pid.' '.TIMEOUT;  // Launch KillProcess with TimeOut
		$pidTimeOut =  $this->background($cmdTimeOut, $ID);
             }
             else {                  
                   if ($this->isSoap)  throw new SoapFault("server03","Server Error: Can't launch process ".$ID); 
		   else return array("error" => "Server Error: Can't launch process ".$ID);
	     }
	     return array('ID' => $ID);
	  }
/**
*          GET STATUS
*
* Receives as argument jobID in array format 
* Checks process (by PID).
* If the process is completed, checks the files of the results.
*
* @param     	list $data
* @return    	string  LongQuery
*/ 
       public function GetStatus($data) {
	  
	    if(is_object($data)) {
		  $vars = get_object_vars($data); 
		  $this->isSoap = true;  
	      }
	    else 
                   $vars = $data;

	    $ID = $vars['ID'];
            $isPlot = strncmp($ID, 'HP', 2) === 0; 

	    $filename = pidDir.$ID;
	     if (Verbose) fwrite(log,pidDir.$ID.PHP_EOL); 
/*Checks whether the file with process PID is created, if not - error message*/
	    if (!file_exists($filename))               
 	       return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no such process ID')); 
          
	    $pid = file_get_contents($filename);  // Read pid from file   
	    $status = $this->is_running($pid); // Testing if php process running yet 

	    if ($status == 'COMPLETED')	 
               if ($isPlot) {
		 if (Verbose) fwrite(log,finalDir.$ID.'.png'.PHP_EOL); 
		  if (!file_exists(finalDir.$ID.'.pdf') && !file_exists(finalDir.$ID.'.png'))  
		        return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no plot'));	
               }
               else {
		    if (Verbose) fwrite(log,finalDir.$ID.'.xml'.PHP_EOL);
		    if (!file_exists(finalDir.$ID.'.xml'))  
		        return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no result table'));	    
	      }
	  
// Result array according to WSDL requirements          
	  $ResultInfo = array('Status' => array('ID' => $ID, 'status' => $status,'description' => $this->statusdescription)); 

 	  return $ResultInfo;
       }

/**
*    GET RESULTS
*
* @param     	list $data
* @return    	string  
*/ 
       public function GetResult($data) { 

	    if(is_object($data)) {
		      $vars = get_object_vars($data); 
                      $this->isSoap = true; 
	      }
	    else $vars = $data;

            $ID = $vars['ID'];
	    $isPlot = strncmp($ID, 'HP', 2) === 0; 
            
          
	    $filePID = pidDir.$ID;
	    $fileError = errorDir.$ID;
            
// Taking information about errors during the script. If file is empty - no errors.
 	    if (file_exists($fileError)) $fileInfo = file_get_contents($fileError);
	    else $fileInfo = 'No info';
            
		$votName = $ID.'.xml'; // Name of result file
//             }
	    if (!file_exists(finalDir.$votName)) 
		      return array('ResultInfo' =>
                          array('ID' => $ID, 'resultURI' => 'No file','fileInfo' => $fileInfo,
				  'status' => 'Error: ','description' => ' unknown error'));   

 	    $resultURI = webAlias. $votName;	//REMETTRE   TODO
	          
// Result array according to WSDL requirements  
	    $ResultInfo = array('ResultInfo' =>
                          array('ID' => $ID,'resultURI' => $resultURI,'fileInfo' => $fileInfo,
				  'status' => 'COMPLETED','description' => 'Data Is Ready')); 
	    

	    if (file_exists($filePID)) unlink($filePID); 
	    if (file_exists($fileError)) unlink($fileError);
 
	    if (file_exists(resultDir.$ID)) $this->rrmdir(resultDir.$ID); // Deleting temporary folders

 	  return $ResultInfo;
       }

/**
*            LONG TIME QUERY
*
*   The Long Time Query  is being used for plotting with 'Mission' layout
*  
*/

	 public function  Abort($data) {

	    if(is_object($data)) {
		      $vars = get_object_vars($data); 
                      $this->isSoap = true; 
	      }
	    else $vars = $data;

            $ID = $vars['ID'];

	    $filename = pidDir.$ID;
	    	    
	    if (!file_exists($filename)) 
		return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => 'no such process ID')); 
          
	    $pid = file_get_contents($filename);  // Read pid from file  
	  	    
	    if ($pid) {
		exec("kill -9 $pid");
             }
             else {  
		   return array('Status' => array('ID' => $ID, 'status' => 'ERROR','description' => "Server Error: Can't kill process")); 
	      }
	   
	    $status = 'ABORTED';
	    $this->statusdescription = 'query aborted'; 
	    
	    if (file_exists($filePID)) unlink($filename);
	    if (file_exists(resultDir.$ID)) $this->rrmdir(resultDir.$ID); // Deleting temporary folders

	    return array('Status' => array('ID' => $ID, 'status' => $status,'description' => $this->statusdescription));
	}


/**
*            LONG TIME QUERY
*
*   The Long Time Query  is being used for plotting with 'Mission' layout
*  
*/

public function  LongTimeQuery($data) {

             if(is_object($data)) {
		      $vars = get_object_vars($data);
		      $this->isSoap = true;
             }
	    else $vars = $data; 

	    if (($result = $this->checkArgs($vars, true)) != null) return $result; 

// Set different HELIOPREFIX
            $this->HELIOPREFIX = 'HP';
	    $ID = $this->setID();  // unique JobID

            $this->IP = $this->getIPclient(); // Get ClientIP
 
	    if ($this->ddCheckUser() != 0) {        
		    if ($this->ddLogin() != 0) { // DD Login if user is not registered
			 if ($this->isSoap) throw new SoapFault("server00","Server Error: AMDA Login procedure failed"); 
                         else return array("error" => "Server Error: AMDA Login procedure failed");
                     } 	 

		    if ($this->ddCheckUser() != 0) { 
			if ($this->isSoap) throw new SoapFault("server01","Server Error: Check User procedure failed");
			else return array("error" => "Server Error: CheckUser procedure failed");
		    }
	    }

 	      if (!$this->makeDir($ID, true)) { // Make all needed dirs
		 if ($this->isSoap) throw new SoapFault("server02","Server Error: Can't make needed dirs"); 
		 else return array("error" => "Server Error: Can't make needed dirs");
	    }
// Duplicate time intervals to have the same number as missions
            if (count($this->start) === 1 &&  count($this->missions) > 1) {
                 for ( $i = 0; $i < count($this->missions); $i++) {
                   $startArr[] = $this->start[0];
                   $stopArr[] = $this->stop[0];
                 }
		  $this->start = $startArr;
		  $this->stop  = $stopArr;
	    }

            $helioRequest = new stdClass();  
	    $helioRequest->missions = $this->missions;
            $helioRequest->start = $this->start;
            $helioRequest->stop = $this->stop;
	    $helioRequest->IP = $this->IP;
	    $helioRequest->ID = $ID;
            $helioRequest->user = $this->user;

	    $argument = urlencode(serialize($helioRequest));

/* Launch php script multi[one]Plot.php in background with argument */
            
	    $cmd = 'php '.wsDir.'/helio-des/multiPlot.php '.$argument; 	
	    $pid =  $this->background($cmd, $ID);
     
/* Save PID in the folder: ex. /home/budnik/Amda-Helio/DDHTML/WebServices/HelioPID
   file named by jobID ($ID)
*/
	    if ($pid) {
                $filename = pidDir.$ID;
	        $file = fopen($filename,"w");
	        fwrite($file,$pid);
	        fclose($file);
 
		$cmdTimeOut = 'php '.wsDir.'/helio-des/killPID.php '.$ID.' '.$pid.' '.TIMEOUT;  // Launch KillProcess with TimeOut
		$pidTimeOut =  $this->background($cmdTimeOut, $ID);
             }
             else {                  
                   if ($this->isSoap)  throw new SoapFault("server03","Server Error: Can't launch process ".$ID); 
		   else return array("error" => "Server Error: Can't launch process ".$ID);
	      }
	    return array('ID' => $ID);

	  }
}

?>
