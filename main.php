<?php
//Required to overide PHP timeout
ini_set('max_execution_time', 300);
/*
//Program Order:
//Get reservations from database using date
//Data retrived: Anchor.reservation.*
//Use venue_id from Anchor.reservation to collect token and passwords for
//api calls
//Call Tables API passing token password and api_tables_reference to function
//Store data should it match up
//Test cover value from both databases.
//If match then OK
//If contradicting then store data from both databases
//Build table at end for email confirmation with contradicting record information
*/
  //Date to be queried
  $dateStart = '2016-11-11';
  //Initial query for usage in primary Anchor database query
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
  ";

  //Queries Anchor database and stores within an array()
  $rows = databaseQuery($dateStart, $initialQuery);

  //Array for storage of contradicting records
  $errorReservation = array();

  if($rows === false){
    print('Failure.');
  } else{
    foreach ($rows as $key => $row) {
      //Relevant IDs
      $databaseId = $row[0];
      $tablesReservationId = $row[41];

      //Required for token, key combinations in API call
      $venueId = $row[6];

      //Used to store data in an array for use in email compilation.
      $allStarData = null;
      $tablesData = null;

      //Used to compare cover count between the different databases.
      $allStarCovers = $row[16];
      $tablesCovers = null;

      //Required for API usage
      $token = getToken($venueId);
      $password = getPassword($venueId);

      //Call Tables API for reservation using api_tables_reference from Anchor database
      getTablesViaApi($token, $password, $tablesReservationId);

      //Test that $tablesCovers contains valid data
      if(isset($tablesCovers)){
        //Test both databases contain the same number of covers
        if($tablesCovers != $allStarCovers){
          //Push contradicting table entities to array for email compilation at a later stage
          array_push($errorReservation, $row ,$tablesData);
        }
      }
    }
  }

  //Decides on output if there are contradicting records
  if((empty($errorReservation) && ($rows != false))) {
    //No contradicting records
    print("No Contradicting Records.");
  } else{
    //Contradicting records
    reportCompilation($errorReservation);
  }

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

  //Array to store all retrieved records
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
  //Open API stream
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

  //Used to parse the json data
  $jfo = null;

  //Required for cover comparison in main program
  $covers = null;

  //Close API stream
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

    //Required to detect contradicting records
    global $tablesCovers;
    $tablesCovers = $covers;
  }
}

/*
//Builds output data for report compilation
//Only if contradicting data has been detected
*/
function reportCompilation($errorReservation){
  for($i = 0; $i < count($errorReservation); $i++){
    var_dump($errorReservation[$i]);
    print('</br></br>');
    $i++;
    var_dump($errorReservation[$i]);
    print('</br></br>');
  }
}

 ?>
