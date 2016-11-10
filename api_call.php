<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

$url = 'https://api.izone-app.com/v2/gm/reservations/314636851?token=005fa9e1-6615-467a-bebd-3c61c5eb0582&password=c0267f84-6e32-4ddc-a9f7-9ff70d969aef';

$username = '005fa9e1-6615-467a-bebd-3c61c5eb0582';
$password = 'c0267f84-6e32-4ddc-a9f7-9ff70d969aef';
// $reservation_id = '400';
//
// //Get cURL Response
// $curl = curl_init($url);
// print('Stage1');
// //Set cURL options
//
//   curl_setopt($curl, CURLOPT_URL, $url);
//   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//   curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
//   print_r($curl);
// //Send request and save response
// $result = curl_exec($curl);
// print_r($result);
// if($result === false){
//   $info = curl_getinfo($curl);
//   curl_close($curl);
//   die('Error Occured during curl exec. Additional info: ' . var_export($info));
// }
//
// // $decoded = json_decode($result);
// // if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
// //     die('error occured: ' . $decoded->response->errormessage);
// // }
// // echo 'response ok!';
// // var_export($decoded->response);
//
// print($result);
// //Close Request
// curl_close($curl);
// // header('Content-type: text/xml');
// // $file = read('https://api.znl-qa02.com/v2/gm/availableTables?date=2016-04-04&partysize=2&token=2989a769-95b7-4011-8274-0992197097b5&password=ae875aea-e0b7-4c94-b7f1-c80ba3f55e13&times=17:00,17:15,17:30,17:45,18:00,18:15,18:30,18:45,19:00');
// // echo $file;

$client = new Client(['base_url' => 'http://api.izone-app.com/v2/gm/reservations/']);

$request = $client->get('droplets', ['auth' => [$username, $password])

$statusCode = $request->getStatusCode();

if ($statusCode >= 200 && $statusCode < 300) {
    $json = $request->json(); // Returns JSON decoded array of data.
    /$xml = $request->xml(); // Returns XML decoded array of data.
    $body = $request->getBody(); // Returns the raw body from the request.
}

 ?>
