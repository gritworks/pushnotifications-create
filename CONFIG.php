<?php
//https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/BinaryProviderAPI.html#//apple_ref/doc/uid/TP40008194-CH13-SW1
if (!defined('APNS_HOST')) define('APNS_HOST', 'https://api.development.push.apple.com');
//if (!defined('APNS_HOST')) define('APNS_HOST', 'https://api.push.apple.com);
if (!defined('APNS_PORT')) define('APNS_PORT', 443);
if (!defined('APNS_CERT'))  define('APNS_CERT', 'certificate.pem');
if (!defined('APNS_PASS')) define('APNS_PASS', 'password');
if (!defined('FCM_HOST')) define('FCM_HOST', 'fcm.googleapis.com/fcm/send');
if (!defined('FCM_SERVER_KEY')) define('FCM_SERVER_KEY', 'fcm server key');

?>