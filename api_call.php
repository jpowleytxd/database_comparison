<?php

//Variable set_include_pat
$url = 'https://api.izone-app.com/v2/gm/reservations/';
$token ='005fa9e1-6615-467a-bebd-3c61c5eb0582';
$password = 'c0267f84-6e32-4ddc-a9f7-9ff70d969aef';

$reservationId = '314636851';

$curl = curl_init();

$concat = $url . $reservationId . '?token=' . $token . '&password=' . $password;
print_r($concat);
curl_setopt_array($curl, array(
  CURLOPT_URL => $concat,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json",
    "cache-control: no-cache",
    "postman-token: 37790892-106f-ea84-283d-40d96a6b11e5"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}

 ?>
