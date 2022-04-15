<?php
//load rc config file

require( __DIR__ . '/../../config/config.inc.php');

//load config file

require( __DIR__ . '/config.inc.php');

//load function file

require( __DIR__ . '/function.php');

//read all users prefs


//Roundcube


$dsnArray = formatDSN($config['db_dsnw']);
$dsn = $dsnArray[0];
$username = $dsnArray[1];
$password = $dsnArray[2];

try {
  $conn = new PDO($dsn, $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

$mail_host = $config["mail_host"];
$sql = "SELECT username,preferences FROM users WHERE mail_host='$mail_host' ;";
$requete = $conn->query($sql);

$tab =  setPreferencesArray($requete,$config);


//ISPConfig


$dsnArray_ispc = formatDSN($config['dsn_ispconfig']);
$dsn = $dsnArray_ispc[0];
$username = $dsnArray_ispc[1];
$password = $dsnArray_ispc[2];

try {
  $conn_ispc = new PDO($dsn, $username, $password);
  // set the PDO error mode to exception
  $conn_ispc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

$sql = "SELECT email,maildir FROM mail_user,mail_domain WHERE mail_user.email LIKE CONCAT('%',mail_domain.domain) AND postfix='y' AND mail_user.server_id=".$config['server_id']." AND active='y';";
$requete_ispc = $conn_ispc->query($sql);

$tabPrefs = matchPrefstoEmail($requete_ispc,$tab,$config);

//Read today's date

$monthDay = date('d');
$weekDay = date('l');

$daily = $weekDay != "Saturday" && $weekDay != "Sunday";

$weekly = $weekDay == $config['day_of_weekly_report'];

$monthly = $monthDay == $config['day_of_monthly_report'];

//parse junkdirs and send report

$listeMail = array();
$sortedJunkDirectory = array();
$arrayEmailKeys = array_keys($tabPrefs);
foreach ($arrayEmailKeys as $email){
	$currentAdress = $tabPrefs[$email];
	array_splice($listeMail,0);
	$frequency = $currentAdress["frequency"];
	if ($currentAdress["maxlength"] > $config['default_maxlength']) { $maxlength = $config['default_maxlength']; } else { $maxlength = $currentAdress['maxlength'];  }
	$maildir = $currentAdress["maildir"];
	if ($frequency!="never" && file_exists("$maildir/Maildir/.Junk/cur")&& file_exists("$maildir/Maildir/.Junk/dovecot-uidlist") &&(($frequency == "weekly" && $weekly)||($frequency=="monthly" && $monthly)||($frequency=="daily" && $daily))){
		print_r($email."\n");
		// Get all Junk files
       		$junkDirectory = array();
		$junkDirectoryCurHandle = opendir("$maildir/Maildir/.Junk/cur");
		while (false !== ($junkCurFile = readdir($junkDirectoryCurHandle))){
			if ($junkCurFile != "." && $junkCurFile != ".."){
				$junkDirectory[] = "$maildir/Maildir/.Junk/cur/".$junkCurFile;
			}
		}
		$junkDirectoryNewHandle = opendir("$maildir/Maildir/.Junk/new");
                while (false !== ($junkNewFile = readdir($junkDirectoryNewHandle))){
                        if ($junkNewFile != "." && $junkNewFile != ".."){
                                $junkDirectory[] ="$maildir/Maildir/.Junk/new/". $junkNewFile;
                        }
                }

		//Sort spam by date (new first)
   		array_splice($sortedJunkDirectory,0);
	    	foreach ($junkDirectory as $junk){
	      		$date = filemtime($junk);
      			$sortedJunkDirectory["$date"] = $junk;
    		}
	    	krsort($sortedJunkDirectory);

		$nbMails=0;
		$uidFile = file("$maildir/Maildir/.Junk/dovecot-uidlist");
		foreach ($sortedJunkDirectory as $junk){
       			if ($junk != '.' && $junk != '..' && $nbMails<$maxlength){
		        	$file = file($junk);

				//Get sender
                		$sender = getSender($file);

				//Get date
       		      		$date = getMailDate($file);

				//Get junk_subject
		        	$junk_subject = getSubject($file, $config);

				//Get spam score
				$spam_score = getSpamScore($file);
				print_r($date."\n");
				//Get mail uid
				$uid = getUID($uidFile,$junk);
				$mail["sender"]=iconv_mime_decode($sender);
       				$mail["date"]=$date;
				$mail["subject"]=iconv_mime_decode($junk_subject);
				$mail["spam_score"]=$spam_score;
				$mail["uid"]=$uid;

       	      			array_push($listeMail,$mail);
				$nbMails++;
       			}
		}

		if (!empty($listeMail)){
		   	$date = getdate()["mday"];
    			$date .= getdate()["mon"];
	    		$date .= getdate()["year"];
	    		$subject = $config['subject'];
			$message = "";
			$table = "";
			$template_mail = file($config["path_to_mail"]);
			$table_line = array_keys(preg_grep("/{{spam_table}}/",$template_mail))[0];
			$table .= '<table style="border:1px solid; border-collapse:collapse">';
			$table .= '<tr>';
			$table .= '<th style="border:1px solid">Objet</th>';
			$table .= '<th style="border:1px solid">Envoyé par</th>';
			$table .= '<th style="border:1px solid">Date</th>';
			$table .= '<th style="border:1px solid">Spam Score</th>';
			$table .= '</tr>';
	    		foreach ($listeMail as $mail){
      				$table .= '<tr style="border:1px solid">';
				$table .= '<td style="border:1px solid">'.$mail["subject"].'</td>';
				$table .= '<td style="border:1px solid">'.htmlspecialchars($mail["sender"]).'</td>';
				$table .= '<td style="border:1px solid">'.$mail["date"].'</td>';
				$table .= '<td style="border:1px solid">'.$mail["spam_score"].'</td>';
				$table .= '<td style="border:1px solid; width : 50px">';
				$table .= '<a href="https://mail4.ircf.fr/?_task=mail&_uid='.$mail["uid"].'&_mbox=Junk&_action=plugin.junk_report.not_junk">Rétablir</a>';
				$table .= '</td>';
				$table .= '<td style="border:1px solid; width : 70px">';
				$table .= '<a href="https://mail4.ircf.fr/?_task=mail&_uid='.$mail["uid"].'&_mbox=Junk&_action=show">Voir le mail</a>';
				$table .= '</td>';
				$table .= '</tr>';
			}
			$table .= '</table>';
			$count = 0;
			$added_table = false;
			foreach ($template_mail as $line){
				if ($count == $table_line){
					$message .= $table;
					$added_table = true;
				}else{
					$message .= $line;
				}
				$count++;
			}
			if (!$added_table){
				$message .= $table;
			}
			mail($email,$subject,$message,implode("\r\n", $header));
			sleep($config['sleep_time']);
			closedir($junkDirectoryCurHandle);
			closedir($junkDirectoryNewHandle);
		}
	}
}
