<?php

include './PushNotification.class.php';

$pn = new PushNotification();


$device_token="";// from iphone se
$payload=array("title"=>"hello","message"=>"message");
$pn->sendIos($device_token,$payload);

echo ".ok";

?>


