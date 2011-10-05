<?php
/** 
*   @file multiRequest.php
*   @brief  
*
*   @version  $Id: $
*   @todo rewrite as class
*/


     require_once 'DES_ini.php';
     echo ('PROCESSING START'.PHP_EOL);
     if (Verbose) fwrite(log,'PROCESSING START'.PHP_EOL);
//   exit(); 
        
// functions => object: user; IP; ID; function; mission; starttime; stoptime;  hash array of params    
   $helioRequest = unserialize(urldecode($argv[1]));	

  if (Verbose) {
	    $rr = print_r($helioRequest,true);
            fwrite(log,$rr.PHP_EOL);
  }		
//  exit(); 
    $IP = $helioRequest->IP;
    $ID = $helioRequest->ID;
    $user = $helioRequest->user;
    $functions = $helioRequest->requests;
    $parameter = null;
 if (Verbose) fwrite(log,'ID = '. $ID.PHP_EOL);
    $resDirName = resultDir.$ID; // Define a temporary  directory for results

     if (!file_exists(functionsXml)) {  // DES functions description
        errorProcessing($ID, "InternalError00: no functions description file"); 
	 if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);          
	die();
    }
    $dom = new DomDocument("1.0");
    $dom->load(functionsXml);
 if (Verbose) fwrite(log,'functionsXml = '. functionsXml.PHP_EOL);
     $i=0;
 
     chdir($resDirName."/RES"); // Down to working directory

// Decode conditions and make all requests for AMDA 	      
    foreach ($functions as $function) {
      
      $mission = $function->mission;
      $func = $function->func;
if (Verbose) fwrite(log,'$mission = '. $mission.PHP_EOL);
if (Verbose) fwrite(log,'$func = '. $func.PHP_EOL);      
 
      $startTime = strtotime($function->starttime);
      $endTime = strtotime($function->stoptime);
      $arrayStartTime[] = $function->starttime;
      $arrayStopTime[]  = $function->stoptime;
 
      makeSearchList($startTime, $endTime); // put request times into search.list

/*   Get list of parameters used in the function
*   find function in functions.xml file
*   NEW  find all parameters after ':'; select parameter and delate : with parameter
*/       
	$parameters[] = $function->parameter;

	 $timeTable = $mission.'_'.$func;
// 	for ($k=0; $k<count($parameters); $k++){
// 	  $timeTable = $timeTable.'_'.$parameters[$k];	//TODO to change
// if (Verbose) fwrite(log,'$parameters[$k] = '. $parameters[$k].PHP_EOL);  
// 	}
	 
// if (Verbose) fwrite(log,'$timeTable = '. $timeTable.PHP_EOL);  
	$timeTableName = $mission.'_'.$func.'_'.$function->parameter[0];
 	$timeTableList[] = $timeTableName;
	if (Verbose) fwrite(log,'$timeTable = '. $timeTable.PHP_EOL); 
	if ($function->parameter[0]){
	  $parameter = $function->parameter[0];
	  if (Verbose) fwrite(log,'$parameter= '.$parameter.PHP_EOL);
	}
            
      $thisFunction =  $dom->getElementById($func);
      if ($thisFunction == null){
	errorProcessing($ID, "InternalError01: function $func is not implemented in AMDA-HELIO yet");
        if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	die();
      }	
	
// find predefined arguments in functions.xml file
      $args = $thisFunction->getElementsByTagName('arg');
      if (count($function->arg) != $args->length) {
	errorProcessing($ID, "InternalError02: function $func : arguments number is ".count($function->arg).
                             " expected number is ".$args->length);
        if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	die();
      }
    

//TODO if not parameters  
//   Get parameter used in the function 



      
//   $params = $thisFunction->getElementsByTagName('param');  
//   velocity_magnitude = $param;  ex.sw(1) to find in AmdaParametersBrief.xml
         
      if (!file_exists(amdaParametersXml)) {  // Internal file to connect DES - AMDA parameters
	  errorProcessing($ID, "InternalError03: no AMDA - DES translation file"); 
          if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	 die();
      }
                  
      $domParam = new DomDocument("1.0");
      $domParam -> load(amdaParametersXml);
//    Find tag with predefined mission 	
      $thisMission = $domParam->getElementById($mission);
      if ($thisMission == null) {
	errorProcessing($ID, "InternalError04: $mission is not found in AMDA - DES translation table");
	if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	die();
      }	
//    Find parameter tag  (V, N, B etc)	
      if (trim($parameter)){	
//  if (Verbose) fwrite(log,'$parameter2= '.$parameter.PHP_EOL);	
	$thisParam = $thisMission->getElementsByTagName(trim($parameter)); 

	if ($thisParam){
 	  if (!$thisParam->item(0)){ 
	      errorProcessing($ID, "InternalError05: $parameter is not found in AMDA - DES translation table");
	      if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	      die();
	    }
	   }

// get AMDA parameter UNIQUE ID by pair Mission -  Parameter alias (V, N, B...)
//       if ($thisParam)
 	$thisParamName = $thisParam->item(0)->getAttribute('name');
      }
          
// Get template file to calculate function 

      $resFile = expressionDir.$func.'.res';
 
      if (!file_exists($resFile)) {
	errorProcessing($ID, "InternalError06: no template file for function $func");
	if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);
	die();
      }

// Fill compound arguments   
      $compounds =  $thisFunction->getElementsByTagName('compound');
 
      if ($compounds -> length > 0)
        foreach ($compounds as $compound) {

	   $cmpdName =  $compound->getAttribute('name');          
           $cmpdExpr = $compound->getAttribute('operation');
           $cmpdKeyValue = $function->arg[$compound->getAttribute('primary')];  
 	   $expression = '$function->arg["'.$cmpdName.'"]='.$cmpdKeyValue.$cmpdExpr.';';
  
           eval($expression);         
	}

// replace aliases by real values 		
      $fileContents = file_get_contents($resFile);		
      foreach ($function->arg as $key => $value) {
	    $fileContents = str_replace($key, $value, $fileContents);
      }

      $lines = explode(PHP_EOL,$fileContents);
      
      $lines[1] = str_replace("parameter", $thisParamName, $lines[1]);
      $n1 = strpos($thisParamName,"(");
      
      if (Verbose) fwrite(log,'$lines[1]= '.$lines[1].PHP_EOL);
      if ($n1 !== false){
	  $paramsArgs = explode("(", $thisParamName);
	  $paramArg = $paramsArgs[0];
      }
      else $paramArg = $thisParamName;
      $lines[2] =  str_replace("parameter", $paramArg, $lines[2]);
       if (Verbose) fwrite(log,'$lines[2]= '.$lines[2].PHP_EOL);

      $chain = $lines[1];
//TODO dispatch for multiples Vars
      $vars[0] = trim($lines[2]);
      $fileContents = implode(PHP_EOL,$lines);
	      
      $fileInfo = $chain.'    '.$vars;	   
      $fileRes = fopen(searchRes,"w");
      fwrite($fileRes,$fileContents);
      fclose($fileRes); 
	
      $myParamBuilder = new ParamBuilder(); // create IDL PARAMCALCUL PROCEDURE 
 
//  Process   Local Params without codes if they exist     
      if (file_exists(XML_BASE_DIR."LocalParamsList.xml")) { 
                $localParams = new DomDocument('1.0');
                $localParams->load(XML_BASE_DIR."LocalParamsList.xml");
                $xp = new domxpath($localParams);               
                foreach ($vars as $var) {  
                             $paramTag = $xp->query('//PARAM[.="'.$var.'"]');
                             if ($paramTag -> length !== 0)                            
                                              $myParamBuilder->paramLocalBuild($var);       
		}
	    }
  
      $myParamBuilder->makeParamCalcul($chain, $vars, "");

// Run command
      $cmd = DDBIN."DD_Search ".$user." ".$IP." ".DDPROJECT." ".DDPROJLIB; 
 if (Verbose) fwrite(log,'$cmd = '.$cmd.PHP_EOL);    
      $cmdResult = system($cmd);
      if ($cmdResult === false) {
		    errorProcessing($ID,$cmdResult);
	  if (Verbose) fwrite(log,'ERROR PROCESSING'.PHP_EOL);     
      }
      if (file_exists('../TT/helio.xml')) {
	  rename('../TT/helio.xml', '../TT/helio'.sprintf("%02d",$i).'.xml');
	}
      $i++;              
     }
// Processing is finished, now post-processing service

     chdir(resultDir.$ID."/TT");
 
     $newHelioResult = new DomDocument("1.0");
     $domXml = new DomDocument("1.0");
     $root = $newHelioResult->createElement('MDES');
     $newHelioResult->appendChild($root);

     for ($j = 0; $j < $i; $j++){	 
	if (file_exists("helio".sprintf("%02d",$j).".xml")){ 
	    $domXml->load("helio".sprintf("%02d",$j).".xml");
 	    // Le noeud que nous voulons importer dans le nouveau document
	    $source = $domXml->getElementsByTagName("Source")->item(0);
	    $strSource = $source->nodeValue;
	    // parsing Source
	    $sourceArray = parseSource($strSource);
	    // add data for INFO information in xml files
	    $node = $domXml->getElementsByTagName("TimeTable")->item(0);
	    $node->setAttribute('Name',$timeTableList[$j]);
	    //  Delete ';' in the end of <Chain> Ask ob Bob e-mail from 20/07/2011
	    $chainBob = $domXml->getElementsByTagName("Chain")->item(0);
	    $chainToChange = $chainBob->nodeValue;
	    $newChain = rtrim(trim($chainToChange),";");

// 	    //	Add MISSION_INSTRUMENT (Anja's request see e-mail 20/07/2011) 
// 	   
	    $functionDescription = explode('_',$timeTableList[$j]); //  [0]-mission, [1]-function, [2]-param 
	    $dataset = $dom->getElementById($functionDescription[2])->parentNode;

	    $datasetClass = trim($dataset->getAttribute('class'));

	    $missionHFE = $dom->getElementById(trim($functionDescription[0])); 

	    foreach ($missionHFE->getElementsByTagName('dataset') as $miss){
		if (trim($miss->getAttribute('class')) == $datasetClass){
		    $instr=trim($miss->getAttribute('instrument')); 
	      }
	    }
if (Verbose) fwrite(log,'$instr= '.$instr.PHP_EOL);

 	    $functionData = $dom->getElementById(trim($functionDescription[1]));
if (Verbose) fwrite(log,'$functionDescription[1]= '.$functionDescription[1].PHP_EOL);

 	    $description = $functionData->getElementsByTagName('description')->item(0)->nodeValue; 
	    $description = str_replace("PARAM",trim($functionDescription[2]), $description);
	    $description = str_replace("OP DELTAF",trim($function->arg['DELTAF']), $description);
  if (Verbose) fwrite(log,'$description = '.$description.PHP_EOL);


	    //  Add TIME_RANGE    (FOR Bob)	   
	    $node->appendChild($domXml->createElement('TIME_RANGE','FROM:'.$arrayStartTime[$j].' TO:'.$arrayStopTime[$j]));
	    //  Add New Chain	 
	    $node->appendChild($domXml->createElement('newChain',$newChain));
	    //  Add Instrument	  
	    $node->appendChild($domXml->createElement('Instrument',$instr));
	    //  Add Time_Step	   
	    $node->appendChild($domXml->createElement('Time_Step',$sourceArray['Time_Step']));
	    // Add Function_Description	   
	    $node->appendChild($domXml->createElement('Function_Description',$description));
	    //  Add Creation_Date in 'iso8601'	  
	    $node->appendChild($domXml->createElement('Creation_Date',date("c")));
 
	    // Importation du noeud, ainsi que tous ses files, dans le document
	    $node = $newHelioResult->importNode($node, true);
	    // Et on l'ajoute dans le le noeud racine "<MDES>"
	    $newHelioResult->documentElement->appendChild($node);	  
	}
     }    
       $newHelioResult->save('helioResult.xml'); // keep XML result as backup - if there is converting problem
 
       xml2Vot($ID, $newHelioResult);
     
       if (file_exists(resultDir.$ID)) rrmdir(resultDir.$ID);              

    
  if (Verbose) fclose(log); 

/*========================================================================
*     functions
*=========================================================================*/
// Transformation into VOtable

   function xml2Vot($ID, $newHelioResult) {
 
	$xslt = new XSLTProcessor();    
	// Chargement du fichier XSL
	$xsl = new DomDocument();   
	$xsl -> load(XML_BASE_DIR."/xml2votMulti.xsl");

      // Import de la feuille XSL
	$xslt -> importStylesheet($xsl);
	  
	$vot = new DomDocument();  
	if (!$vot->loadXML($xslt->transformToXML($newHelioResult))) {
		errorProcessing($ID, 'can\'t convert Result table into VOT'); 
		die();
         }
                  
	if (!$vot->save("VOT.xml")) {
		errorProcessing($ID, 'can\'t save VOT'); 
		die();
         }
  if (Verbose) fwrite(log,'ID = '. $ID.PHP_EOL);
        if (!rename('VOT.xml',finalDir.$ID.'.xml')) {	        
               errorProcessing($ID, 'can\'t copy  VOT to final destination'); 
	      die();
       }


   }
 	
   function rrmdir($dir){
      if (is_dir($dir)) {
	$objects = scandir($dir);
 
	foreach ($objects as $object) { // Recursively delete a directory that is not empty and directorys in directory 
	  if ($object != "." && $object != "..") {  // If object isn't a directory recall recursively this function 
	    if (filetype($dir."/".$object) == "dir") 
                     rrmdir($dir."/".$object);
            else 
                    unlink($dir."/".$object);
	  }
	}
	reset($objects);
	rmdir($dir);
      }
    }  
/* Time Interval into AMDA Format DDD:HH:MM:SS */
    function timeInterval2Days($TimeInterval){

	$divDays = 60*60*24;
	$nbDays = floor($TimeInterval / $divDays);
	$divHours = 60*60;
	$nbHours = floor(($TimeInterval - $divDays*$nbDays)/$divHours);
	$nbMin = floor(($TimeInterval - $divDays*$nbDays - $divHours*$nbHours)/60);
	$nbSec = $TimeInterval - $divDays*$nbDays - $divHours*$nbHours - $nbMin*60;
 
	$DD = sprintf("%03d",   $nbDays);			// format ex. 005 not 5
	$HH = sprintf("%02d",   $nbHours);			// format ex. 25 
	$MM = sprintf("%02d",   $nbMin);			// format ex. 03 not 3
	$SS = sprintf("%02d",   $nbSec);			// format ex. 02 not 2

	return  $DD.':'.$HH.':'.$MM.':'.$SS;

    }

/* Start Time into AMDA format YYYY:DOY-1:HH:MM:SS */
    function startTime2Days($startTime){
         
         $ddStart = getdate($startTime); 
	 $date_start = sprintf("%04d",$ddStart["year"]).":".sprintf("%03d", $ddStart["yday"]).":"
                                   .sprintf("%02d",$ddStart["hours"]).":".sprintf("%02d",$ddStart["minutes"]).":"
                                   .sprintf("%02d",$ddStart["seconds"]);
        return $date_start;
    }
 
/*  Convert into AMDA format and write StartTime and TimeInterval into search.list */ 
    function makeSearchList($startTime, $endTime){

      $TIMEINTERVAL = timeInterval2Days($endTime - $startTime);
      $STARTTIME = startTime2Days($startTime);
                  
      $fileS = fopen(searchList, "w");
      fwrite($fileS, $STARTTIME.PHP_EOL.$TIMEINTERVAL.PHP_EOL);
      fclose($fileS); 

  }

      function errorProcessing($ID, $errorKey){

     	$fp = fopen(errorDir.$ID, 'a');
	fwrite($fp, $errorKey);
	fclose($fp);
      }

      function parseSource($strSource){
/*  Explode string line source to array with delimiter ';'
    Example of source:
    AMDA Search: Time_Step: 600.0s; Data_absence_is_gap_for_gaps >    5 Data_Sampling_Times; Start_Time:2006-02-20T21:33:10 Time_Interval:369d01h07m
    !!!!   TODO: Ask Elena if the source's  structure always is the same
*/
	$arrSource = explode(';',$strSource);

	if (($arrSource) && (count($arrSource) !== 0)){
	 
	  for ($i=0; $i<count($arrSource); $i++){
	      switch ($i) {
		  case 0:
		      $tmpSource = explode(':',$arrSource[$i]);
		      $finalDesc[trim($tmpSource[1])] = trim($tmpSource[2]);
		      break;
		  case 1:
		      $finalDesc['Function_Description']=trim($arrSource[$i]);
		      break;

	      }
	  }
	  return $finalDesc;
	}
	else return null;
      }

?>
