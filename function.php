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

	foreach($requete_ispc->fetchAll() as $personne){
		if (isset($tab[$personne['email']])){
    			$tabPrefs[$personne['email']] = array("frequency" => $tab[$personne['email']]['frequency'],
								"maxlength" => $tab[$personne['email']]['maxlength'],
	 							"maildir" => $personne['maildir']);
  		}
	}

	return $tabPrefs;

}
