<?php
/* $Id: DDserverWebHelio.php, v 0.1 2011/02/10 natasha based on file DDserverWeb.php,v 1.4 2008/08/22 14:57:45 elena Exp $ */
/** 
*   @file HelioServerWeb.php
*   @brief PHP SoapServer for Helio WebServices
*
*   @date 14.01.2011
*   @version 0.1 
*/

class ServiceLongQueryHelio {

    private $des_ws;    

	 function __construct(){
		    
/* ===============================================================================================================
  In AMDA registered 5 logins helio (helio1,helio2, ..., helio5  ) with a password helio.
  In this version only login helio1 used.
  Variables user, passwd & vars are global variables.
  =============================================================================================================== */		    
		    
		$this->des_ws = new DES_WS();

	    }

/**
*            LONG TIME QUERY
*
*   The Long Time Query is not implemented in MDAS, because  the AMDAs role in HELIO seek outdata according to conditions.  
*   Long Time Query hasn't these elements. 
*
* @param     	list $data
* @return    	nothing     
*/

	 function  LongTimeQuery($data) {
	      throw new SoapFault("request00","Request Error: LongTimeQuery is not implemented in MDES services ");
	  }
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
    
	 function  LongQuery($data) {

/*  Decoding Input */
	    if ($data->STARTTIME == null) throw new SoapFault("request01","Request Warning: No Start Time => Global Mission Start would be used");
	    if ($data->ENDTIME == null) throw new SoapFault("request02","Request Warning: No End Time => Global Mission End would be used");
/*  Transforming Data in arrays with delimiter ',' */   
	    $start =  explode(',', $data->STARTTIME);
	    $stop = explode(',',$data->ENDTIME); 
/* Control of coherence between start and end date == number start & end time must be equal*/
            if (count($start) != count($stop)) throw new SoapFault("request05","Request Error: The number of StartTime and EndTIme is different");
/* Control of availability FROM and transforming Data in arrays with delimiter ',' */	    
	    if ($data->FROM == null) throw new SoapFault("request03","Request Error: No FROM statement"); 
            $missions = explode(",",$data->FROM);

/* Control of coherence between number of missions(FROM) and number of date
   Two possibility: one time interval 		- several missions
		    several time intervals	- several missions (number of missions and intervals must be equal)
*/  
	    if ((count($start) != count($missions)) && (count($start) > 1)) throw new SoapFault("request06","Request Error: The number of StartTime and missions is different");
/* Control of coherence between start and end date == each start must be less than end time*/	     	    
	    for ($i = 0; $i < count($start); $i++)
		if ($start[$i] > $stop[$i])  throw new SoapFault("request07","Request Error: StartTime > EndTIme in ".$i." interval");
/* Control of availability WHERE and transforming Data variable */	
	    if ($data->WHERE == null) throw new SoapFault("request04","Request Error: No WHERE statement"); 
	    $where = $data->WHERE;
	    
	    return $this->des_ws->LongQuery($start, $stop, $missions,$where);
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
       function GetStatus($data) {

	  $ID =  $data->ID;

 	  return $this->des_ws->GetStatus($ID);
       }

/**
*    GET RESULTS
*
* @param     	list $data
* @return    	string  
*/ 
       function GetResult($data) {
	    
	  $ID = $data->ID;

	  return $this->des_ws->GetResult($ID);
       }

}

  require_once 'HelioMDESserverWeb_ini.php';
  

  ini_set("soap.wsdl_cache_enabled", "0"); // desactive le cache WSDL
  $server = new SoapServer("./HelioLongQueryService.wsdl");
  $server->setClass("ServiceLongQueryHelio");
  $server->handle();



?>


