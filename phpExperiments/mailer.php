<?php
set_include_path('src');
include 'ICS.php';

$properties = array(
  'dtstart' => 'now',
  'dtend' => 'now + 30 minutes'
);
$ics = new ICS($properties);
$email = new PHPMailer();
$bodytext = "fuck you";
$email->From      = 'you@example.com';
$email->FromName  = 'Your Name';
$email->Subject   = 'Message Subject';
$email->Body      = $bodytext;
$email->AddAddress( 'unbrace3@gmail.com' );


$email->AddAttachment( $ics , 'calendar.ics' );

return $email->Send();
