<?php
//load rc config file

include('/opt/roundcubemail/config/config.inc.php');

//load config file

include('config.inc.php');

//read all users prefs

//Roundcube

preg_match('|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)(/([0-9a-zA-Z_/\.]*))|',
     $config['db_dsnw'],$matches);
$dsnArray=array(
    $matches[1].':host='.$matches[5].';dbname='.$matches[7],
    $matches[2],
    $matches[4]
);

$dsn = $dsnArray[0];
$username = $dsnArray[1];
$password = $dsnArray[2];

try {
  $conn = new PDO($dsn, $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}
$mail_host = $config["mail_host"];
$sql = "SELECT username,preferences FROM users WHERE mail_host='$mail_host' ;";
$requete = $conn->query($sql);

foreach ($requete->fetchAll() as $personne){
  $preferences = unserialize($personne['preferences']);
  if (substr($personne['username'],-9) != ".disabled"){
    if (isset($preferences['frequency']) && isset($preferences['maxlength'])){
      $tab[$personne['username']] = array("frequency" => $preferences['frequency'], "maxlength" => $preferences['maxlength']);
    }else{
      $tab[$personne['username']] = array("frequency" => $config['default_frequency'], "maxlength" => $config['default_maxlength']);
    }
  }
}

//ISPConfig

preg_match('|([a-z]+)://([^:]*)(:(.*))?@([A-Za-z0-9\.-]*)(/([0-9a-zA-Z_/\.]*))|',
     $config['dsn_ispconfig'],$matches);
$dsnArray=array(
    $matches[1].':host='.$matches[5].';dbname='.$matches[7],
    $matches[2],
    $matches[4]
);

$dsn = $dsnArray[0];
$username = $dsnArray[1];
$password = $dsnArray[2];

try {
  $conn_ispc = new PDO($dsn, $username, $password);
  // set the PDO error mode to exception
  $conn_ispc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

//$sql = "show tables";
$sql = "SELECT email,maildir FROM mail_user,mail_domain WHERE mail_user.email LIKE CONCAT('%',mail_domain.domain) AND postfix='y' AND mail_user.server_id=8 AND active='y';";
$requete_ispc = $conn_ispc->query($sql);

foreach($requete_ispc->fetchAll() as $personne){
  if (isset($tab[$personne['email']])){
    $tabPrefs[$personne['email']] = array("frequency" => $tab[$personne['email']]['frequency'], "maxlength" => $tab[$personne['email']]['maxlength'], "maildir" => $personne['maildir']);
  }
}

//Read today's date

$monthDay = date('d');
$weekDay = date('l');

if ($weekDay == $config['day_of_weekly_report'])
  $weekly = true;
else
  $weekly = false;

if ($monthDay == $config['day_of_monthly_report'])
  $monthly = true;
else
  $monthly = false;

//parse junkdirs and send report

$listeMail = array();
$sortedJunkDirectory = array();
$temp = 0;
$arrayEmailKeys = array_keys($tabPrefs);
foreach ($arrayEmailKeys as $email){
	$currentAdress = $tabPrefs[$email];
	array_splice($listeMail,0);
	if ($temp<50){
		$frequency = $currentAdress["frequency"];
        	$maxlength = $currentAdress["maxlength"];
	  	$maildir = $currentAdress["maildir"];
	  	if ($frequency!="never" && file_exists("$maildir/Maildir/.Junk/cur")&&(($frequency == "weekly" && $weekly)||($frequency=="monthly" && $monthly)||($frequency=="daily"))){
            		$junkDirectory = scandir("$maildir/Maildir/.Junk/cur");
	    		array_splice($sortedJunkDirectory,0);
		    	foreach ($junkDirectory as $junk){
		      		$date = filemtime("$maildir/Maildir/.Junk/cur/$junk");
	      			$sortedJunkDirectory["$date"] = $junk;
	    		}
		    	ksort($sortedJunkDirectory);
		    	$nbMails=1;
	    		$uidFile = file("$maildir/Maildir/.Junk/dovecot-uidlist");
	    		foreach ($sortedJunkDirectory as $junk){
              			if ($junk != '.' && $junk != '..' && $nbMails<=$maxlength){

			        	$file = file("$maildir/Maildir/.Junk/cur/$junk");

					//Get sender
        	        		$sender = substr(preg_grep("/^From:/",$file)[array_keys(preg_grep("/^From:/",$file))[0]],6);

					//Get date
        		      		if (explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])){
				  		if (substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1],0,-3))
		    					$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1],0,-3);
		  				else
		    					$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[0],0,-3);
					}else{
		          			$date = substr(explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1],0,12);
        				}

					//Get junk_subject
			        	if (preg_grep("/^Subject:/",$file))
	        		  		$junk_subject = substr(preg_grep("/^Subject:/",$file)[array_keys(preg_grep("/^Subject: /",$file))[0]],9);
	        			else
	          				$junk_subject = $config['no_subject'];

					//Get mail uid
					$formattedJunk = explode(":","$junk")[0];
					$uid = explode(" ",preg_grep("/$formattedJunk/",$uidFile)[array_keys(preg_grep("/$formattedJunk/",$uidFile))[0]])[0];

					$mail["sender"]=$sender;
              				$mail["date"]=$date;
					$mail["subject"]=$junk_subject;
					$mail["uid"]=$uid;

					if ($mail["sender"] == "") echo "sender : $email $junk \n";
					if ($mail["date"] == "") echo "date : $email $junk \n";
					if ($mail["subject"] == "") echo "subject : $email $junk \n";
        	      			array_push($listeMail,$mail);
					$nbMails++;
              			}
			}
		}

		if (!empty($listeMail)){
			$temp++;
		    	$adresseMail = "test@akiway.com";
		    	$date = getdate()["mday"];
	    		$date .= getdate()["mon"];
		    	$date .= getdate()["year"];
		    	$subject = $config['subject'];
	    		$message = '<html><body>';
		    	$message .= '<table style="border:1px solid; border-collapse:collapse"><tr><th>Objet</th><th style="border:1px solid">Envoyé par</th><th>Date</th><th style="border:1px solid">UID</th></tr>';
		    	foreach ($listeMail as $mail){
	      			$message .= '<tr style="border:1px solid"><td>'.$mail["subject"].'</td><td style="border:1px solid">'.$mail["sender"].'</td><td>'.$mail["date"].'</td><td style="border:1px solid"><a href="https://mail3.ircf.fr/?_task=mail&_uid='.$mail["uid"].'&_action=plugin.junk_report.not_junk">Rétablir</a></td></tr>';
   	    		}
		    	$message .= '</table></body></html>';
		    	mail($adresseMail,$subject,$message,implode("\r\n", $header));
			usleep($config['sleep_time']);
		}
	}
}
