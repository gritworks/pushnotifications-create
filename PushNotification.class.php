
<?php

class PushNotification { 

    function __construct() {

    	/* ios */
        //$this->apnsHost="gateway.push.apple.com";
    	$this->apnsHost="gateway.sandbox.push.apple.com";
    	$this->apnsPort = 2195;
    	$this->apnsCert="certificate.pem";
    	$this->pass="password";


    	$this->gmcHost="android.googleapis.com/gcm/send";
    	$this->gmcApiKey="gmc api key";
    }
    
    // send ios push notification
    function sendIos($device_token,$payload=array("title"=>"the title","message"=>"the message")){

		// payload gets flattened to parent on ios.
      $body['aps'] = array(
         "alert"=>$payload['message'],
         "sound"=>"default",
         "badge"=>"1",
         "payload" => $payload
         );
		// Encode the payload as JSON
      $payload = json_encode($body);

      $streamContext = stream_context_create();
      stream_context_set_option($streamContext, 'ssl', 'local_cert', './certificates/'.$this->apnsCert);
      stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->pass);

      $apns = stream_socket_client('ssl://' . $this->apnsHost . ':' . $this->apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

      $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device_token)) . chr(0) . chr(strlen($payload)) . $payload;


      echo $apns;

      fwrite($apns, $apnsMessage);

		//socket_close($apns);
      fclose($apns);

  }

  // send android push notification
  function sendAndroid($deviceToken,$payload=array("title"=>"the title","message"=>"the message")){
   $ids = array( $device_token );


   $post = array('registration_ids'  => $ids,'data' => $payload);
   $headers = array( 'Authorization: key=' . $this->gmcApiKey,'Content-Type: application/json');
   $ch = curl_init();
   curl_setopt( $ch, CURLOPT_URL, 'https://'.$this->gmcHost );
   curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
   curl_setopt( $ch, CURLOPT_POST, true );
   curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
   curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
   curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
   $result = curl_exec( $ch );
   if ( curl_errno( $ch ) ) {
        $result = $result . 'GCM error: ' . curl_error( $ch );
   }
   curl_close( $ch );
   }
} 



?>
