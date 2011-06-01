<?php

   require_once 'HelioMDESserverWeb_ini.php';

     $user = 'helio1';

// functions => object: IP; ID; function; mission; starttime; stoptime;  hash array of params    
   $helioRequest = unserialize(urldecode($argv[1]));	
		
/*===========================================================================================
*   Decoding and unserialization received argument
*/ 
    $timeTableList = array();
    $IP = $helioRequest->IP;
    $ID = $helioRequest->ID;
    $functions = $helioRequest->requests;
    $parameter = null;
// Define a temporary  directory to results
    $resDirName = resultDir.$ID;

//  Load XML from  'DesFunctions.xml' file  == file describing implemented AMDA functions
    $dom = new DomDocument("1.0");
    if (file_exists(xmlDir.'DesFunctions.xml')) $dom -> load(xmlDir.'DesFunctions.xml');


    $i=0;

    foreach ($functions as $function) {
// Decoding functions      
      $mission = $function-> mission;
      $func = $function-> func;
      $timeTableList[] = $mission.'_'.$func;
// Time in string format to  time format
      $startTime = strtotime($function->starttime);
      $endTime = strtotime($function->stoptime);
      
      $resDirName = resultDir.$ID;
      createSearchListFile($startTime, $endTime, $ID);
//   Get list of arguments used in the function
// find searching function in functions.xml file
    // NEW  find all parameter after #; select parameter and delate # with parameter
      
 	$listFunc = explode("#",$func);
	if (count($listFunc) > 1){
	  $parameter = $listFunc[1];
	  $func = $listFunc[0];
	}
      
      
      $thisFunction =  $dom->getElementById($func);
      if ($thisFunction == null){
	errorProcessing($ID, "InternalError01: function is not implemented in AMDA-HELIO yet");
	die();
      }		
// find predefined arguments in functions.xml file
      $args = $thisFunction->getElementsByTagName('arg');
      if (count($function -> arg) != $args->length) {
	errorProcessing($ID, "InternalError02: function arguments value are not completed");
	die();
      }
//   Get parameter used in the function
      $params = $thisFunction->getElementsByTagName('param');
//   velocity_magnitude = $param;  ex.sw(1) to find in /home/budnik/Amda-Helio/DDHTML/WebServices/XML/AmdaParametersBrief.xml
//      $param = $params->item(0)->getAttribute('name');
       $domParam = new DomDocument("1.0");
      if (file_exists(xmlDir.'AmdaParametersBrief.xml')) $domParam -> load(xmlDir.'AmdaParametersBrief.xml');
//    Find tag with predefined mission 	
      $thisMission = $domParam->getElementById($mission);
      if ($thisMission == null) {
	errorProcessing($ID, "InternalError03: mission is not implemented in AMDA-HELIO yet");
	die();
      }	
//    Find tag with this parameter for replace  'velocity_magnitude'	
      if ($parameter){		
	$thisParamNames = $thisMission->getElementsByTagName(trim($parameter)); 
	$thisParamName = $thisParamNames->item(0)->getAttribute('name');
      }
//TODO if not parameters     
      
// Get request file with function calcul

      $resFile = expressionDir.$func.'.res';
      if (!file_exists($resFile)) {
	errorProcessing($ID, "InternalError04: function is not describe in .res file yet");
	die();
      }      			
           $fileContents = file_get_contents($resFile);		
            foreach ($function->arg as $key => $value) {
	         $fileContents = str_replace($key, $value, $fileContents);
           }
	    $lines = explode("\n",$fileContents);
	    
// 	    $lines[1] = str_replace($param, $thisParamName, $lines[1]);
	    $lines[1] = str_replace("parameter", $thisParamName, $lines[1]);
	    $n1 = strpos($thisParamName,"(");
	     
	    if ($n1 !== false){
		$paramsArgs = explode("(", $thisParamName);
		$paramArg = $paramsArgs[0];
	    }
	    else $paramArg = $thisParamName;
// 	   $lines[2] =  str_replace($param, $paramArg, $lines[2]);
	    $lines[2] =  str_replace("parameter", $paramArg, $lines[2]);

	    $chain = $lines[1];
//TODO dispatch for multiples Vars
	    $vars[0] = trim($lines[2]);
	    $fileContents = implode("\n",$lines);
// Down to working directory            
               chdir(resultDir.$ID."/RES");
	    
	    $fileInfo = $chain.'    '.$vars;
	    $fileResult = 'search.res';
	    $fileRes = fopen($fileResult,"w");
	    fwrite($fileRes,$fileContents);
	    fclose($fileRes); 
 
// create IDL PARAMCALCUL PROCEDURE            
	    $myParamBuilder = new ParamBuilder();
            $myParamBuilder->makeParamCalcul($chain, $vars, "");

// File creation and Get PID and STATUT
	    $cmd = DDBIN."DD_Search ".$user." ".$IP." ".DDPROJECT." ".DDPROJLIB;  
	    $cmdResult = system($cmd);
	    if ($cmdResult === false) errorProcessing($ID,$cmdResult);
//	    system($cmd);
	    chdir(resultDir.$ID."/TT");
	    if (file_exists('helio.xml')) rename('helio.xml', 'helio'.$i.'.xml');
	    $i++; 
// COPY RESULT TO FINAL RESULT             
     }
     chdir(resultDir.$ID."/TT");
 
     $newHelioResult = new DomDocument("1.0");
     $domXml = new DomDocument("1.0");
     $root = $newHelioResult->createElement('MDES');
     $root = $newHelioResult->appendChild($root);
     for ($j = 0; $j < $i; $j++){	 
	if (file_exists("helio".$j.".xml")){ 
	    $domXml->load("helio".$j.".xml");
	    // Le noeud que nous voulons importer dans le nouveau document
	    $node = $domXml->getElementsByTagName("TimeTable")->item(0);
	    $node ->setAttribute('Name',$timeTableList[$j]);
 

//	    $newHelioResult->load("helioResult.xml");
	    // Importation du noeud, ainsi que tous ses files, dans le document
	    $node = $newHelioResult->importNode($node, true);
	    // Et on l'ajoute dans le le noeud racine "<MDES>"
	    $newHelioResult->documentElement->appendChild($node);	  
	}
     }    
      $newHelioResult->save("helioResult.xml");

 
// Transformation in VOtable
 
	$xslt = new XSLTProcessor();   
	// Chargement du fichier XSL
	$xsl = new domDocument();   
	$xsl -> load(XML_BASE_DIR."/xml2votMulti.xsl");

      // Import de la feuille XSL
	$xslt -> importStylesheet($xsl);
	  
	$vot = new domDocument();  
	$vot -> loadXML($xslt -> transformToXML($newHelioResult));
	$vot -> save("VOT.xml");
    
  

    function timeInterval2Days($TimeInterval){
// Calculating number of Days, Hours, Min & Sec in interval
	$divDays = 60*60*24;
	$nbDays = floor($TimeInterval / $divDays);
	$divHours = 60*60;
	$nbHours = floor(($TimeInterval - $divDays*$nbDays)/$divHours);
	$nbMin = floor(($TimeInterval - $divDays*$nbDays - $divHours*$nbHours)/60);
	$nbSec = $TimeInterval - $divDays*$nbDays - $divHours*$nbHours - $nbMin*60;
// Transforming Integers to String with special format
	$DD = sprintf("%03s",   (string)$nbDays);			// format ex. 005 not 5
	$HH = sprintf("%02s",   (string)$nbHours);			// format ex. 25 
	$MM = sprintf("%02s",   (string)$nbMin);			// format ex. 03 not 3
	$SS = sprintf("%02s",   (string)$nbSec);			// format ex. 02 not 2

// Time Interval in AMDA Format DDD:HH:MM:SS
	return  $DD.':'.$HH.':'.$MM.':'.$SS;

    }

    function startTime2Days($startTime){
	
// Transforming times to AMDA format: YYYY:NBD:HH:MM:SS    *NBD - The day of the year (starting from 0)
	$STARTTIME = date("Y:z:H:i:s",$startTime);
	$st = explode(":",(string)$STARTTIME);
// DataTime to string	   
	$YYST = sprintf("%04s",   $st[0]);			// format ex. 005 not 5 
	$DDST = sprintf("%03s",   $st[1]);			// format ex. 005 not 5
	$HHST = sprintf("%02s",   $st[2]);			// format ex. 25 
	$MMST = sprintf("%02s",   $st[3]);			// format ex. 03 not 3
	$SSST = sprintf("%02s",   $st[4]);			// format ex. 02 not 2 
	return $YYST.":".$DDST.":".$HHST.":".$MMST.":".$SSST;
    }

    function createSearchListFile($startTime, $endTime, $ID){

      $TIMEINTERVAL = timeInterval2Days($endTime - $startTime);
      $STARTTIME = startTime2Days($startTime);
 
// Down to working directory            
      chdir(resultDir.$ID."/RES");

      $fileInfo = $TIMEINTERVAL;

// Add StartTime\n and TimeInterval in search.list file in the $ID directory
      $fileSearch = 'search.list';
      $fileS = fopen($fileSearch,"w");
      fwrite($fileS, $STARTTIME."\n".$TIMEINTERVAL."\n");
      fclose($fileS); 

  }

      function errorProcessing($ID, $errorKey){
     	$fp = fopen(errorDir.$ID, 'w');
	fwrite($fp, $errorKey);
	fclose($fp);
      }

?>
