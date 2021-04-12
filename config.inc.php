<?php

/*
*
*	junk_report configuration file
*
*/

$junk_report_config = array();

//Path to the domain file
$dir = "/var/vmail";

//Default value for the frequency of sending the junk report
$default_frequency = "never";

//Default value for the number of junk mails in the junk report
$default_maxlength = 100;

//Day of the weekly report
//Specified in letter
$day_of_weekly_report = "Sunday";

//Day of the monthly report
//Specified with two-digit number
$day_of_monthly_report = 01;

//Alternative text if the mail does not contain a subject
$no_subject = "Aucun objet";

//Header settings
$header[] = "From : technique@ircf.fr";
$header[] = 'Content-type: text/html; charset=UTF-8';

//Subject of the junk report
$subject = "Rapport de spam";
