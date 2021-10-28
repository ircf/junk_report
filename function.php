<?php

/*
*	Takes the result of the sql query on the basis of roundcube and returns associating for each user its frequency and maxlength.
*	If these two values are not defined they are defined by the default value in the config file.
*/

function setPreferencesArray($requete,$config){

	foreach ($requete->fetchAll() as $personne){
  		$preferences = @unserialize($personne['preferences']);
  		if (substr($personne['username'],-9) != ".disabled"){
    			if (isset($preferences['frequency']) && isset($preferences['maxlength'])){
      				$tab[$personne['username']] = array("frequency" => $preferences['frequency'], "maxlength" => $preferences['maxlength']);
    			}else{
      				$tab[$personne['username']] = array("frequency" => $config['default_frequency'], "maxlength" => $config['default_maxlength']);
    			}
  		}
	}
	return $tab;
}

/*
*	Formats a dsn of the form mysql://user:pass@hostname/databasename and returns an array(dsn, user, pass) to use PDO.
*/

function formatDSN($dsn){

	preg_match('|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)(/([0-9a-zA-Z_/\.]*))|',
		$dsn,$matches);
	$dsnArray=array(
   		$matches[1].':host='.$matches[5].';dbname='.$matches[7],
    		$matches[2],
    		$matches[4]
	);

	return $dsnArray;

}

/*
*	Match tabPrefs with the list of users retrieved in ispconfig.
*	Return an array associating these users with their frequency, their maxlength and their email.
*/

function matchPrefstoEmail($requete_ispc,$tab,$config){
	$tabPrefs = array();
	foreach($requete_ispc->fetchAll() as $personne){
		if (isset($tab[$personne['email']])){
    			$tabPrefs[$personne['email']] = array("frequency" => $tab[$personne['email']]['frequency'],
								"maxlength" => $tab[$personne['email']]['maxlength'],
	 							"maildir" => $personne['maildir']);
  		}
	}

	return $tabPrefs;

}

/*
*	Get the sender of a mail from his stored file given in parameters.
*	Return the sender mail adress
*/

function getSender($file){

	$fromLine = preg_grep("/^From:/",$file)[array_keys(preg_grep("/^From:/",$file))[0]];
	if (strpos($fromLine,"<")) {
		return $sender = substr($fromLine, strpos($fromLine, "<") + 1, strpos($fromLine, ">") - strpos($fromLine, "<") - 1);
	} else {
		return $sender = substr($fromLine, 6);
	}
}

/*
*	Get the date of a mail from his stored file given in parameters.
*	Return the date translated in French
*/

function getMailDate($file){
	if (explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])){
		if (@explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1])
			$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1],0,-3);
		else
			$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[0],0,-3);
	}else{
		$date = substr(explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1],0,12);
	}
	$arrayDate = explode(" ",$date);
	$arraySize = count($arrayDate);
	$day = $arrayDate[$arraySize-3];
	$month = $arrayDate[$arraySize-2];
	$year = $arrayDate[$arraySize-1];
	$monthTab["Jan"] = "Janvier";
	$monthTab["Feb"] = "Fevrier";
	$monthTab["Mar"] = "Mars";
	$monthTab["Apr"] = "Avril";
	$monthTab["May"] = "Mai";
	$monthTab["Jun"] = "Juin";
	$monthTab["Jul"] = "Juillet";
        $monthTab["Aug"] = "Aout";
        $monthTab["Sep"] = "Septembre";
        $monthTab["Oct"] = "Octobre";
        $monthTab["Nov"] = "Novembre";
	$monthTab["Dec"] = "Decembre";

	$formattedDate = $day." ".$monthTab[$month]." ".$year;
	return $formattedDate;
}

/*
*       Get the spam-score of a mail from his stored file given in parameters.
*       Return the spam-score
*/

function getSpamScore($file){
	return substr(preg_grep("/^X-Spam-Score:/",$file)[array_keys(preg_grep("/^X-Spam-Score:/",$file))[0]],14);
}

/*
*	Get the uid of a mail from the dovecot-uid file
*	Get in parameters the uid-file as array and the name of the mail file
*/

function getUID($uidFile, $junk){
	$formattedJunk = explode(":",substr(strrchr("$junk","/"),1))[0];
        return explode(" ",preg_grep("/$formattedJunk/",$uidFile)[array_keys(preg_grep("/$formattedJunk/",$uidFile))[0]])[0];
}

/*
*       Get the subject of a mail from his stored file given in parameters.
*       Return the spam-score or the default value set in the config.
*/

function getSubject($file, $config){
	if (preg_grep("/^Subject:/",$file) && !empty(array_keys(preg_grep("/^Subject: /",$file)))){
		$junk_subject = substr(preg_grep("/^Subject:/",$file)[array_keys(preg_grep("/^Subject: /",$file))[0]],9);
	}else{
    		$junk_subject = $config['no_subject'];
	}
	return $junk_subject;
}
