<?php

ini_set('max_execution_time', 300);
/*
//Program Order:
//Get reservation from database using reservation id
//Data retrived: api_tables_reference, id, date_created, venue_id
//Use venue_id to collect token and passwords for api calls
//Call Tables API passing token password and reservationId to function
*/
  $dateStart = '2016-11-11';
  $initialQuery = "
    SELECT *
    FROM reservations
    INNER JOIN reservations_activities ON (reservations.id = reservation_id)
    WHERE activity_id = 2
    AND DATE(date_start) = '" . $dateStart . "'
    AND date_start IS NOT NULL
    AND confirmed = 1
    AND deleted = 0
    AND cancelled = 0
    AND is_enquiry = 0
    AND rejected = 0
    Limit 10
  ";

  $rows = databaseQuery($dateStart, $initialQuery);

  $errorReservation = array();

  if($rows === false){
    print('Failure.');
  } else{
    foreach ($rows as $key => $row) {
      $databaseId = $row[0];
      $tablesReservationId = $row[41];

      $venueId = $row[6];

      $allStarData = null;
      $tablesData = null;

      $allStarCovers = $row[16];
      $tablesCovers = null;

      $token = getToken($venueId);
      $password = getPassword($venueId);

      //print_r("<span style='color: blue;'>Database ID: (" . $databaseId . "). Venue ID: (" . $venueId . "). Token: (" . $token . "). Password: " . $password . "). Tables Reservation: (" . $tablesReservationId . "). </span>");

      getTablesViaApi($token, $password, $tablesReservationId);
      //print('</br>');

      if(isset($tablesCovers)){
        //print('<span style="color: red">');
      //  print('All Star Covers: (<b>' . $allStarCovers . '</b>). ');

        //print('Tables Covers: (<b>' . $tablesCovers . '</b>).');
        //print('</span>');
        //print('</br>');

        if($tablesCovers != $allStarCovers){
          array_push($errorReservation, $row ,$tablesData);
          //print('<b>Cover Difference.</b>');
          //print('</br>');
        }
      } else{
        //print('<span style="color: green;">No Reservation Found Within Tables.</span>');
        //print('</br>');
      }
      //print('</br>');
    }
  }

  if(empty($errorReservation)) {
    print("No Contradicting Records.");
  } else{
    //print("Contradicting Records: </br>");
    reportCompilation($errorReservation);
  }

/*
//TablesReferenceId passed from Here
//To getTablesViaApi function
*/

/*
//Initial function retrieving data from All Star database
//Returns rows[]
*/

function databaseQuery($dateStart, $query){
  //Define Connection
  static $connection;

  //Attempt to connect to the database, if connection is yet to be established
  if(!isset($connection)){
    //Load configuration file
    $config = parse_ini_file('config.ini');
    $connection = mysqli_connect('localhost', $config['username'], $config['password'], $config['dbname']);
  }

  $rows = array();
  $result = null;

  //Connection error handle
  if($connection === false){
    print('Connection Error');
    return false;
  } else{
    //Query the database
    $result = mysqli_query($connection, $query);

    //If query failed, return 'false'
    if($result === false){
      print('Query Failed.');
      return false;
    }

    //Fetch all the rows in the array
    while($row = mysqli_fetch_row($result)){
      $rows[] = $row;
    }
  }
  return $rows;
}

/*
//functions for use with venue token password combinations
*/
function getToken($venueId){
  $token = null;
  switch($venueId){
    case "1":
      $token = 'bc287a9f-6c33-4341-9e3a-5e147b54c1c1';
      break;
    case "2":
      $token = 'cf07ebc4-e033-45e6-9908-7b284b0e1e76';
      break;
    case "3":
      $token = '005fa9e1-6615-467a-bebd-3c61c5eb0582';
      break;
    case "4":
      $token = 'baa8b87d-59b2-4445-9a38-0322bc0feab0';
      break;
    case "5":
      $token = '1c9725ce-0987-4281-a508-3c7fe2b7b548';
      break;
  }
  return $token;
}

function getPassword($venueId){
  $password = null;
  switch($venueId){
    case "1":
      $password = '08b6bd37-863e-46df-8813-f9ad922c511d';
      break;
    case "2":
      $password = '2f538e47-fd10-4d76-9105-fd3ec80f8cbb';
      break;
    case "3":
      $password = 'c0267f84-6e32-4ddc-a9f7-9ff70d969aef';
      break;
    case "4":
      $password = '189df099-cf09-4cb1-8239-2d80b576c2ac';
      break;
    case "5":
      $password = '94c11d80-e149-473a-a6b2-f235d1175f2f';
      break;
  }
  return $password;
}

/*
//Main function to call Tables API
//Params: Location Token/Password Combination
//Params: api_tables_reference
*/
function getTablesViaApi($token, $password, $reservationId){
  $url = 'https://api.izone-app.com/v2/gm/reservations/';
  //$token ='005fa9e1-6615-467a-bebd-3c61c5eb0582';
  //$password = 'c0267f84-6e32-4ddc-a9f7-9ff70d969aef';

  //$reservationId = '314636851';

  $curl = curl_init();

  $concat = $url . $reservationId . '?token=' . $token . '&password=' . $password;
  //print_r($concat);
  curl_setopt_array($curl, array(
    CURLOPT_URL => $concat,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
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

  $jfo = null;
  $covers = null;

  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    //echo $response;

    $jfo = json_decode($response, true);
    //var_dump($jfo);

    //$covers = $jfo['partySize'];
    if(isset($jfo['partySize'])){
      $covers = $jfo['partySize'];
      global $tablesData;
      $tablesData = $jfo;
    }  else{
      $covers = null;
    }

    //print('</br></br>');
    //var_dump($jfo);
    global $tablesCovers;

    $tablesCovers = $covers;

  }
}

function reportCompilation($errorReservation){
  print('eff');
}
/*
//End Tables API query
*/
 ?>
