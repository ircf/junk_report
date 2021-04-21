<?php
//load rc config file

include('/opt/roundcubemail/config/config.inc.php');

//load config file

include('config.inc.php');

//read all users prefs

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

foreach($requete->fetchAll() as $personne){
  $preferences = unserialize($personne['preferences']);
  if (substr($personne['username'],-9) != ".disabled"){
    $userDomaine = explode("@",$personne['username']);
    if (isset($preferences['frequency']) && isset($preferences['maxlength'])){
      $tabPrefs[$userDomaine[1]][$userDomaine[0]] = array("frequency" => $preferences['frequency'], "maxlength" => $preferences['maxlength']);
    }else if ($config['default_frequency'] != "never"){
      $tabPrefs[$userDomaine[1]][$userDomaine[0]] = array("frequency" => $config['default_frequency'], "maxlength" => $config['default_maxlength']);
    }
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
$dir = $config['dir'];
$domaineDirectory = scandir($dir);
$sortedJunkDirectory = array();
$temp = 0;
$arrayDomainKeys = array_keys($tabPrefs);
foreach ($arrayDomainKeys as $domaine){
	$currentDomain = $tabPrefs[$domaine];
  	$arrayUserKeys = array_keys($currentDomain);
  	foreach ($arrayUserKeys as $user){
      		$arrayUser = $currentDomain[$user];
      		array_splice($listeMail,0);
      		$adresseMail = "$user@$domaine";
		if ($temp<50){
	  		$frequency = $arrayUser["frequency"];
          		$maxlength = $arrayUser["maxlength"];
	 		if ($frequency!="never" && file_exists("$dir/$domaine/$user/Maildir/.Junk/cur")&&(($frequency == "weekly" && $weekly)||($frequency=="monthly" && $monthly)||($frequency=="daily"))){
            			$junkDirectory = scandir("$dir/$domaine/$user/Maildir/.Junk/cur");
	    			array_splice($sortedJunkDirectory,0);
	    			foreach ($junkDirectory as $junk){
	      				$date = filemtime("$dir/$domaine/$user/Maildir/.Junk/cur/$junk");
	      				$sortedJunkDirectory["$date"] = $junk;
	    				}
	    			ksort($sortedJunkDirectory);
	    			$nbMails=1;
	    			$uidFile = file("$dir/$domaine/$user/Maildir/.Junk/dovecot-uidlist");
	    			foreach ($sortedJunkDirectory as $junk){
              				if ($junk != '.' && $junk != '..' && $nbMails<=$maxlength){

	        				$file = file("$dir/$domaine/$user/Maildir/.Junk/cur/$junk");

						//Get sender
                				$sender = substr(preg_grep("/^From:/",$file)[array_keys(preg_grep("/^From:/",$file))[0]],6);
						echo "sender : $sender \n";

						//Get date
              					if (explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])){
		  					if (substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1],0,-3))
		    						$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[1],0,-3);
		  					else
		    						$date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[0],0,-3);
						}else{
	          					$date = substr(explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1],0,12);
        					}
//		echo "date : $date \n";

						//Get junk_subject
	        				if (preg_grep("/^Subject:/",$file))
	          					$junk_subject = substr(preg_grep("/^Subject:/",$file)[array_keys(preg_grep("/^Subject: /",$file))[0]],9);
	        				else
	          					$junk_subject = $config['no_subject'];
//		echo "subject : $junk_subject \n";
						echo "$adresseMail : $junk \n";

						//Get mail uid
						$formattedJunk = explode(":","$junk")[0];
						$uid = explode(" ",preg_grep("/$formattedJunk/",$uidFile)[array_keys(preg_grep("/$formattedJunk/",$uidFile))[0]])[0];
						$mail["sender"]=$sender;
              					$mail["date"]=$date;
              					$mail["subject"]=$junk_subject;
						$mail["uid"]=$uid;
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
//	    echo "$date";
	    			$subject = $config['subject'];
	    			$message = '<html><body>';
	    			$message .= '<table style="border:1px solid; border-collapse:collapse"><tr><th>Objet</th><th style="border:1px solid">Envoyé par</th><th>Date</th><th style="border:1px solid">UID</th></tr>';
	    			foreach ($listeMail as $mail){
	      				$message .= '<tr style="border:1px solid"><td>'.$mail["subject"].'</td><td style="border:1px solid">'.$mail["sender"].'</td><td>'.$mail["date"].'</td><td style="border:1px solid"><a href="https://mail3.ircf.fr/?_task=mail&_uid='.$mail["uid"].'&_action=plugin.junk_report.not_junk">Rétablir</a></td></tr>';
   	    			}
	    			$message .= '</table></body></html>';
	    			mail($adresseMail,$subject,$message,implode("\r\n", $header));
	  		}
		}
      	}
}
