<?php
include 'CONFIG.php';

class PushNotification{

   function __construct() {
   }
    
   /**********************************************************************/
   // * send ios push notification * char limit whith total payload is 200.
   /**********************************************************************/
   /*
   // http1 protocol
   function sendIos(
      $ids=array(),
      $payload=array(
         "title"=>"the title",
         "message"=>"the message",
         "url" => "url",
      )
   ){
   
      // payload gets flattened to parent on ios.
      $body['aps'] = array(
         "alert"=>$payload['message'],
         "sound"=>"default",
         "badge"=>"1",
      );
         
      // Encode the payload as JSON
      $payload_json = json_encode($body);

      $streamContext = stream_context_create();
      stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT);
      stream_context_set_option($streamContext, 'ssl', 'passphrase', APNS_PASS);
      
      $apns = stream_socket_client('ssl://' . APNS_HOST . ':' . APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

      // catching invalid registration id's
      $apns_errors=array("success"=>"ok","errors"=>array());

      foreach($ids as $device_token) {

         $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device_token)) . chr(0) . chr(strlen($payload_json)) . $payload_json;
         
         try { 
            $fwrite=fwrite($apns, $apnsMessage,8192); // limit buffer size to overcome ssl php bug?
         }catch (Exception $ex) {
            // should fix:  f_write SSl operation failed with code 1 erro 1409F07F SSL3 WRITE_PENDING:bad write entry (error in old php version with https://)
            sleep(5); //sleep for 5 seconds
            $fwrite=fwrite($apns, $apnsMessage,8192); // limit buffer size to overcome ssl php bug?
         }

         if($this->_streamIsClosed($apns)){
            // check apple response if stream has changed..
            $status_code=$this->_checkAppleErrorResponse($apns);
         
            $device=array(
               "status_code"=>$status_code,
               "registration_id"=>$device_token,
            );
            array_push($apns_errors['errors'],$device);

            // re-open the stream as it has been closed by apple
            $apns = stream_socket_client('ssl://' . APNS_HOST . ':' . APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);
         }

      }
      // close stream
      fclose($apns);
      // return info about bad devices
      return json_encode($apns_errors);
   }
   */

   //http2 protocol
   function sendIos( $ids=array(),
   $payload=array(
      'title' => 'test title',
      'message'=>'test message',
      'url' => 'test url',
      )
){

      // reformat payload for new api
      $payload_http2=array(
         "aps"=>array(
            "alert" => $payload['message'],
            "sound" => 'default',
            'vibrate' => 'true',
            "badge" => "1",
            "content-available" => "1",
         ),
         "url"=>$payload['url']
      );

      // for outputting errors
      $apns_errors=array("success"=>"ok","errors"=>array());


      if (!defined('CURL_HTTP_VERSION_2_0')) {
         define('CURL_HTTP_VERSION_2_0', 3);
      }
      //$apple_cert=realpath(APNS_CERT);
      $http2_server = APNS_HOST;   // or 'api.push.apple.com' if production
      $message =  json_encode($payload_http2);
      $app_bundle_id = 'com.schoolsunited.schoolsunited';

      

      $http2ch = curl_init();
      curl_setopt($http2ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

      

      // headers
      $headers = array(
         "apns-topic: {$app_bundle_id}",
         // "User-Agent: My Sender",
         // "apns-push-type: message",
         // "content-available: 1",
         // "apns-priority :5",
      );

      foreach($ids as $token){
         // create endpoint to reach
         $url = "{$http2_server}/3/device/{$token}";

         // other curl options
         curl_setopt_array($http2ch, array(
            CURLOPT_URL => "{$url}",
            CURLOPT_PORT => APNS_PORT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $message,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => APNS_CERT,
            CURLOPT_HEADER => 1,
            CURLOPT_SSLKEYPASSWD => APNS_PASS,
        ));
        // send
        $result = curl_exec($http2ch);
        $status_code = curl_getinfo($http2ch, CURLINFO_HTTP_CODE);
        
        if($status_code!==200){
           $device=array(
            "status_code"=>$status_code,
            "registration_id"=>$token,
            "reason"=>$result,
            );
            array_push($apns_errors['errors'],$device);
        }
        
      }
      
        curl_close($http2ch);
      
        return json_encode($apns_errors);

   }
   

   // *** send android push notification ***
   function sendAndroid(
      $ids=array(),
      $payload=array(
         'title' => 'title',
         'message'=>'message',
         'sound' => 'default',
         'vibrate' => 'true',
         'url' => 'url', 
         "content-available" => "1",
         )
   ){
      
         $post = array('registration_ids'  => $ids,'data' => $payload);
         $headers = array( 'Authorization: key=' . FCM_SERVER_KEY,'Content-Type: application/json');
         $ch = curl_init();
         curl_setopt( $ch, CURLOPT_URL, 'https://'.FCM_HOST );
         curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
         curl_setopt( $ch, CURLOPT_POST, true );
         curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
         curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
         curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
         $result = curl_exec( $ch );
         if ( curl_errno( $ch ) ) {
            $result = $result . 'FCM error: ' . curl_error( $ch );
         }
         curl_close( $ch );
         return $result;

   }
   

   /**********************************************************************/
   // * apple helper functions
   /**********************************************************************/
   // apns check if stream was closed, then there is an error for sure..
   /*
   function _streamIsClosed($apns){
      $null = NULL;
      $socket_select_timeout = 200000;
      $arr=[$apns];
      $changedStream = @stream_select($arr, $null, $null, 0, $socket_select_timeout);
      return $changedStream;
   }

   // after stream is closed check if there is an error response from apple
   private function _checkAppleErrorResponse($apns)
   {
      stream_set_blocking($apns, 0);
      $apple_error_response = fread($apns, 6);
      if ($apple_error_response) {
         $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);
         if ($error_response['status_code'] == '0') {
            $error_response['status_code'] = '0-No errors encountered';
         } else if ($error_response['status_code'] == '1') {
            $error_response['status_code'] = '1-Processing error';
         } else if ($error_response['status_code'] == '2') {
            $error_response['status_code'] = '2-Missing device token';
         } else if ($error_response['status_code'] == '3') {
            $error_response['status_code'] = '3-Missing topic';
         } else if ($error_response['status_code'] == '4') {
            $error_response['status_code'] = '4-Missing payload';
         } else if ($error_response['status_code'] == '5') {
            $error_response['status_code'] = '5-Invalid token size';
         } else if ($error_response['status_code'] == '6') {
            $error_response['status_code'] = '6-Invalid topic size';
         } else if ($error_response['status_code'] == '7') {
            $error_response['status_code'] = '7-Invalid payload size';
         } else if ($error_response['status_code'] == '8') {
            $error_response['status_code'] = '8-Invalid token';
         } else if ($error_response['status_code'] == '255') {
            $error_response['status_code'] = '255-None (unknown)';
         } else {
            $error_response['status_code'] = $error_response['status_code'].'-Not listed';
         }
         return $error_response['status_code'];
      }
   }
   */
}


?>
