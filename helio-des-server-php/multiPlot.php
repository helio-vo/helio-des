<?php
/** 
*   @file multiPlot.php
*   @brief  
*
*   @version  $Id: $
*   @todo rewrite as class
*/
     require_once 'DES_ini.php';
     
     if (Verbose) fwrite(log,'PROCESSING START'.PHP_EOL);
         
// functions => object: user; IP; ID; function; mission; starttime; stoptime;  hash array of params    
   $helioRequest = unserialize(urldecode($argv[1]));	

  if (Verbose) {
	    $rr = print_r($helioRequest,true);
            fwrite(log, $rr.PHP_EOL);
  }
  
     $IP = $helioRequest->IP;
     $ID = $helioRequest->ID;
    
     $missions = $helioRequest->missions;
     $start =  $helioRequest->start;
     $stop =  $helioRequest->stop;
     $user = $helioRequest->user; 

     $isMulti = count($start) > 1;
   //  $isMulti = true;
     $resDirName = resultDir.$ID; // Define a temporary  directory for results
 
     if (!file_exists(plotsXml)) {  // DES plots description
         errorProcessing($ID, "InternalError00: no plots description file"); 

	 if (file_exists(resultDir.$ID)) 
		      rrmdir(resultDir.$ID);          
	 die();
     }

     $dom = new DomDocument("1.0");
     $dom->load(plotsXml);
       
     chdir($resDirName."/RES"); // Down to working directory
  
    $i=0; 
    	      
    foreach ($missions as $mission) {
    
      $fileS = fopen(requestList, "w");  
      $missionTag = $dom->getElementById($mission);   
      $params = $missionTag->getElementsByTagName('param'); 

//TODO calculate deltaY from number of vars          
      $yStart   = 0.1;
      fwrite($fileS,$params->length.PHP_EOL);

      foreach ($params as $param) { 
         $yStop = $yStart + 0.2;
         fwrite($fileS,$param->getAttribute('name').' 0 '.$yStart.' 0.95 '.$yStop.' 0 0 0 0'.PHP_EOL);
         $yStart += 0.2;
      }

      $startTime = strtotime($start[$i]);
      $endTime = strtotime($stop[$i]);
 
      $TIMEINTERVAL = timeInterval2Days($endTime - $startTime);
      $STARTTIME = startTime2Days($startTime);
                         
      fwrite($fileS, $STARTTIME.PHP_EOL.$TIMEINTERVAL.PHP_EOL);
      fclose($fileS); 
      
      $myParamBuilder = new ParamBuilder();  
  
//  Process   Local Params without codes if they exist     
      if (file_exists(XML_BASE_DIR."LocalParamsList.xml")) { 
                $localParams = new DomDocument('1.0');
                $localParams->load(XML_BASE_DIR."LocalParamsList.xml");
                $xp = new domxpath($localParams);               
                foreach ($params as $param) { 
			     $var = $param->getAttribute('name'); 
                             $paramTag = $xp->query('//PARAM[.="'.$var.'"]');
                             if ($paramTag -> length !== 0) {                          
                                              $myParamBuilder->paramLocalBuild($var);   
		             }
		}
	    }
 
// Run command
      if ($isMulti) 
               $DD_cmd = 'DD_PS';
      else
               $DD_cmd = 'DD_Plot';

      $cmd = DDBIN.$DD_cmd." request.list ".$user." ".$IP." ".DDPROJECT." ".DDPROJLIB;  
      $cmdResult = system($cmd);

      if ($cmdResult === false) 
		    errorProcessing($ID,$cmdResult);
 
      if ($isMulti) {
	 if (file_exists('idl.ps')) 
               rename('idl.ps', 'idl'.sprintf("%03d",$i).'.ps');
      }

      $i++;              
     }
  // Processing is finished, now post-processing service
     if ($isMulti) {
	    foreach (glob("idl*.ps") as $aPS) exec("ps2pdf ".$aPS);
	      
	    $cmd =  "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=".finalDir.$ID.".pdf ";
	    foreach (glob("idl*.pdf") as $aPDF) $cmd .=  $aPDF." ";
	    exec($cmd);
	    $urlPlot =  webAlias.$ID.".pdf";
      } 
     else {
              foreach (glob("*.png") as $aPNG) rename($aPNG, finalDir.$ID.".png");
	      $urlPlot =  webAlias.$ID.".png";
// 	      png2Votab($urlPlot, $missions, $start, $stop);
    }

    if (file_exists((finalDir.$ID.".png")) || file_exists((finalDir.$ID.".pdf")))  
	png2Votab($urlPlot, $missions, $start, $stop, $ID);        
   
   if (file_exists(resultDir.$ID)) 
    {
      rrmdir(resultDir.$ID);              
    }
  
  if (Verbose) fclose(log); 

/*========================================================================
*     functions
*=========================================================================*/

   function png2Votab ($urlPng, $missions, $start, $stop, $ID){
	  $xmlPng = new DOMDocument(); 
    // VOTABLE node
	  $votable = $xmlPng->createElementNS('http://www.ivoa.net/xml/VOTable/v1.1', 'VOTABLE');
	  $xmlPng->appendChild($votable);
	  
	  $voVersion = $xmlPng->createAttribute('version');
	  $votable->appendChild($voVersion);

	  $voVersionVal = $xmlPng->createTextNode('1.1');
	  $voVersion->appendChild($voVersionVal);
	  
    // DESCRIPTION
	  $voDescription = $xmlPng->createElement('DESCRIPTION', 'Plot generated by AMDA @ CDPP');
	  $votable->appendChild($voDescription); 
    // RESOURCE
	  $voResource = $xmlPng->createElement('RESOURCE');
	  $votable->appendChild($voResource); 
    // DESCRIPTION RESOURCE
	  $voResource->appendChild($voDescription);

    // INFO
    // Creation Date
	$infoCreationDate = createNewElement($xmlPng, 'INFO', array('name'=>'CreationDate','value'=>date("c"),'ucd'=>'meta.code', 
						      'xtype'=>'iso8601', 'utype'=>'helio:time.time_creation'));
	$voResource->appendChild($infoCreationDate); 

    // Mission 
	for ($i = 0; $i < count($missions); $i++){
	  
	  $missionsString =  $missionsString.$missions[$i];
	  if ($i !== count($missions)-1) 
	      $missionsString =  $missionsString.',';
	}

	$infoMissions = createNewElement($xmlPng, 'INFO', array('name'=>'Missions','value'=>$missionsString,'ucd'=>'meta.code', 
						      'xtype'=>'TODO ask Anja'));
	$voResource->appendChild($infoMissions);  
    //  TimeRange
	$infoTimeRange = createNewElement($xmlPng, 'INFO', array('name'=>'TIME_RANGE','value'=>'FROM:'.implode($start).' TO:'.implode($stop)));
	$voResource->appendChild($infoTimeRange);  
    // TABLE
	$table = createNewElement($xmlPng, 'TABLE', array('name'=>$missionsString.'_PLOT'));
	$voResource->appendChild($table);

    // FIELD
	$field = createNewElement($xmlPng, 'FIELD', array('datatype'=>'char','name'=>'url', 'arraysize'=>'*' ));
	$table->appendChild($field);

    // FIELD DESCRIPTION
	$description2 = $xmlPng->createElement('DESCRIPTION', 'URL of the DES PLOT file');
	$field->appendChild($description2);

    // DATA
	$data = $xmlPng->createElement('DATA');
	$field->appendChild($data);

    // TABLEDATA
	$tabledata = $xmlPng->createElement('TABLEDATA');
	$data->appendChild($tabledata);

    // TR
	$tr = $xmlPng->createElement('TR');
	$tabledata->appendChild($tr);

    // TD
	$td = $xmlPng->createElement('TD', $urlPng);
	$tr->appendChild($td);

	$xmlPng->save(finalDir.$ID.".xml");
// 	$xmlPng->save("/home/budnik/Amda-Helio/DDHTML/WebServices/TEST/testVOtablePnj.xml");
 }

    function createNewElement($domObj, $tag_name, $attributes = NULL)
    {
	$element = ($value != NULL ) ? $domObj->createElement($tag_name, $value) : $domObj->createElement($tag_name);

	if( $attributes != NULL )
	{
	    foreach ($attributes as $attr=>$val)
	  {
            $element->setAttribute($attr, $val);
	  }
	}
    return $element;
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


      function errorProcessing($ID, $errorKey){

     	$fp = fopen(errorDir.$ID, 'a');
	fwrite($fp, $errorKey);
	fclose($fp);
      }
     
?>
