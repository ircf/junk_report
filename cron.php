<?php
//load rc config file

include('/opt/roundcubemail/config/config.inc.php');

//read all users prefs

$dsn = $config['db_dsnw'];



//parse junkdirs and send report

$dir = "/var/vmail";
$listeMail = array();
$domaineDirectory = scandir($dir);
foreach ($domaineDirectory as $domaine){
  if ($domaine != '.' && $domaine != '..' && is_dir("$dir/$domaine")){
    $userDirectory = scandir("$dir/$domaine");
    foreach ($userDirectory as $user){
      //echo "$dir/$domaine/$user";
      if ($user != '.' && $user != '..' && is_dir("$dir/$domaine/$user")){
	if (file_exists("$dir/$domaine/$user/Maildir/.Junk/cur")){
          $junkDirectory = scandir("$dir/$domaine/$user/Maildir/.Junk/cur");
	  array_splice($listeMail,0);
	  $adresseMail="$user@$domaine";
	  foreach ($junkDirectory as $junk){
            if ($junk != '.' && $junk != '..'){

	      $file = file("$dir/$domaine/$user/Maildir/.Junk/cur/$junk");

              $sender = explode(":",preg_grep("/^From:/",$file)[array_keys(preg_grep("/^From:/",$file))[0]])[1];
	      //if (empty($sender)) echo "$dir/$domaine/$user/Maildir/.Junk/cur/$junk";

             if (explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])){
                $date = substr(explode(",",explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1])[0],0,-3);
             }else{
	       $date = substr(explode(":",preg_grep("/^Date:/",$file)[array_keys(preg_grep("/^Date:/",$file))[0]])[1],0,12);
             }

	     if (preg_grep("/^Subject:/",$file))
	       $subject = explode(":",preg_grep("/^Subject:/",$file)[array_keys(preg_grep("/^Subject: /",$file))[0]])[1];
	     else
	       $subject = "Pas d'objet";
	     //if (empty($subject)) echo "$dir/$domaine/$user/Maildir/.Junk/cur/$junk";

	     $mail["sender"]=$sender;
             $mail["date"]=$date;
             $mail["subject"]=$subject;
             array_push($listeMail,$mail);
           }
         }
	 if (!empty($listeMail))
	    print_r($listeMail);
	}
      }
    }
  }
}

