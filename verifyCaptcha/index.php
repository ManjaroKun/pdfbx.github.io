<?php
header('Content-Type: application/json');
include('../settings.php');
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = '';
    return $ipaddress;
}
function curl($url) {
    $ch = curl_init();
    $timeout = 9;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$user_ip = get_client_ip();
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'secret' => $google_reCaptcha_secret_key,
    'response' => $_POST["response"],
    'remoteip' => $_SERVER['REMOTE_ADDR']
]);
$errorsx = array();
$post_data = new stdClass();
$post_datajson = new stdClass();
$resp = curl_exec($ch);

$resp = json_decode($resp);
curl_close($ch);
$isproxy = false;
$redirectto = $lp_domain.'/r/van';
if ($resp->success) {
$post_datajson->success = true;
$api_call = 'http://proxycheck.io/v2/'.$user_ip.'?key='.$proxycheck_api_key.'&vpn=3&asn=1&short=1';
$proxydata = curl($api_call);
try {
   $pcheck = json_decode($proxydata, true);
}
catch (exception $e) {
  $pcheck = 'no';
  array_push($errorsx, $e->getMessage());
}
finally {
   if ($pcheck !== 'no') {
      if ($pcheck['status'] === "ok" || $pcheck['status'] === "warning") {
       if ($pcheck['vpn'] === 'no' && $pcheck['proxy'] === 'no') {
           
           } else {
               $isproxy = true;
              $redirectto = $whitepage;
           }
       } 
   }
}
} else {
$post_datajson->success = false;
$redirectto = $whitepage; //if captcha isn't correct output whitepage in response.
}


$post_datajson->challenge_ts = date("Y-m-d\TH:i:s\Z");
$post_datajson->hostname = $lp_domain;
$post_datajson->isproxy = $isproxy;
$post_datajson->errors = $errorsx;
$post_data->d = json_encode($post_datajson, JSON_UNESCAPED_SLASHES).'|'.$redirectto;
echo json_encode($post_data, JSON_UNESCAPED_SLASHES);
?>