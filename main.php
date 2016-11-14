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
  //$dateStart = date("Y-m-d");
  $dateStart = "2016-11-19";
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
  $rows = databaseQuery($initialQuery);

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
    $file = fopen($dateStart . "-ErrorRecords.txt", "w");
    $outputString = null;

    $outputString = "
    Date: (" . date("d-m-Y") . "). \r\n";
    $outputString = $outputString . "
    Total No. Of Bookings: (" . count($rows) . ").";
    $outputString = $outputString ."
    No Contradicting Records.";

    echo fwrite($file, $outputString);
    fclose($file);
  } else{
    //Contradicting records
    reportCompilation($errorReservation, $rows);
  }

/*
//Initial function retrieving data from All Star database
//Returns rows[]
*/
function databaseQuery($query){
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
//Function to get venues using ID numbers as found in above queries
*/
function getVenueName($venueId){
  $query = "
  SELECT name
  FROM venues
  WHERE id = " . $venueId . "";

  $venueName = null;
  $venueName = databaseQuery($query);

  if(isset($venueName)){
    return $venueName;
  } else{
    return null;
  }
}


/*
//Builds output data for report compilation
//Only if contradicting data has been detected
*/
function reportCompilation($errorReservation, $rows){
  global $dateStart;
  $file = fopen($dateStart . "-ErrorRecords.txt", "w");

  $outputString = null;

  //print("<div style='width: 100%; height: ; border: 5px solid black;'></div></br>");
  $outputString = "
  Date: (" . date("d-m-y") . ").";
  print_r("Date: (" . date("d-m-y") . ")." . "\r\n");
  $outputString = $outputString . "
  Total No. Of Bookings: (" . count($rows) . ").";
  print_r("Total No. Of Bookings: (" . count($rows) . ")." . "\r\n");
  $outputString = $outputString . "
  Total No. Of Correct Bookings: (" . (count($rows) - (count($errorReservation) / 2)) . ").";
  print_r("Total No. Of Correct Bookings: (" . (count($rows) - (count($errorReservation) / 2)) . ")." . "\r\n");
  $outputString = $outputString . "
  Total No. Of Incorrect Bookings: (" . (count($errorReservation) / 2) . ").

  ";
  print_r("Total No. Of Incorrect Bookings: (" . (count($errorReservation) / 2) . ")." . "\r\n");
  print("Lines Written To File: (");

  for($j = 1; $j <= 5; $j++){
    for($i = 0; $i < count($errorReservation); $i++){
      $anchorData = $errorReservation[$i];
      $i++;
      $tablesData = $errorReservation[$i];

      $referenceId = $anchorData[11];

      $anchorId = $anchorData[0];
      $anchorCoverCount = $anchorData[16];
      $anchorVenueId = (int)$anchorData[6];

      $tablesId = $anchorData[41];
      $tablesConfirmation = $tablesData['confirmation'];
      $tablesGuest = $tablesData['guest'];
        $tablesFirstName = $tablesGuest['firstName'];
        $tablesLastName = $tablesGuest['lastName'];
        $tablesEmail = $tablesGuest['email'];
        $tablesMobilePhone = $tablesGuest['mobilePhone'];
      $tablesPartySize = $tablesData['partySize'];
      $tablesReservationTime = $tablesData['reservationTime'];
      $tablesSession =  $tablesData['session'];

      if($anchorVenueId == $j){
        //print("<div style='width: 100%; height: ; border: 5px solid black;'></div></br>");
        $outputString = $outputString . "

        Venue ID : (" . $anchorVenueId . ").";
        $venueNameLookup = getVenueName($anchorVenueId);
        $venueName = null;

        if($venueNameLookup === false){
          //Failed to lookup venue
        } else{
          foreach ($venueNameLookup as $key => $row)
            $venueName = $row[0];
        }

        //var_dump($venueName);
        $outputString = $outputString . "
        Venue Name: (" . $venueName . ").
        ";

        $outputString = $outputString . "
            Reference Id: (" . $referenceId . ").";
        $outputString = $outputString . "
            Anchor Covers : (" . $anchorCoverCount . ").
        ";

        $outputString = $outputString . "
            Tables ID: (" . $tablesId . ").";
        $outputString = $outputString . "
            Tables Confirmation: (" . $tablesConfirmation . ").";
        $outputString = $outputString . "
            First Name: (" . $tablesFirstName . ").";
        $outputString = $outputString . "
            Last Name: (" . $tablesLastName . ").";
        $outputString = $outputString . "
            Email: (" . $tablesEmail . ").";
        $outputString = $outputString . "
            Tables Covers: (" . $tablesPartySize . ").";
        $outputString = $outputString . "
            Reservation Time: (" . $tablesReservationTime . ").";
        $outputString = $outputString . "
            Session: (" . $tablesSession . ").
        ";

        $outputString = $outputString . "
            Link To Booking: (http://anchor.txdlimited.co.uk/index.php/bookings/" . $anchorId . ").
        ";
      }
    }
  }

  echo fwrite($file, $outputString);
  fclose($file);
  print_r(").\r\n");
}

 ?>
