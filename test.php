<?php

    include 'PushNotification.class.php';

    $pn=new PushNotification();

    // sending android note..
    //$ids = array( "efA2pIADklQ:APA91bEoPs5_jdYMWl3YMhj-UcSaKbnsuc6zSGWRa-ZAOG9lhFnBzAkQPzT6gO78kClrX7uhRi9lsGQBUlmjbv3WGa9JQyVp47MOh7EtBPxXG471EXxbTq71XoO6SkTZXcLdEvi-g4GX" );
    //$pn -> sendAndroid($ids);

    //phpinfo();
    // ios
    $ids = array( "c0468bff4acdcb4c92ab02f26207aff63242b70c8f2884a86bfd9c3d3885d79c" , "c0468bff4acdcb4c92ab02f26207aff63242b70c8f2884a86bfd9c3d3885d79d");
    //$pn -> sendIos($ids);

    $errs = $pn -> sendIos($ids);
    //echo($errs);
?>
