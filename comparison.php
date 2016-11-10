<?php

//Function to connect to All Star Lanes database
function db_connect(){
  static $connection;

  if(!isset($connection)){
    $config = parse_ini_file('config.ini');
    $connection = mysqli_connect('localhost', $config['username'], $config['password'], $config['dbname']);
  }

  if($connection === false){
    return mysqli_connect_error();
  }
  print('connected');
  return $connection;
}

//Function to query database passing parameters as $query
function db_query($query){
  $connection = db_connect();

  $result = mysqli_query($connection, $query);

  return $result;
}

//Function seperating rows returned from the query
function db_select($query){
  $rows = array();
  $result = db_query($query);

  //If query failed, return 'false'
  if($result === false){
    return false;
  }

  //Successful
  while($row = mysqli_fetch_assoc($result)){
    $rows[] = $row;
  }

  echo 'Total Results: ' . count($rows);
  return $rows;
}


$rows = db_select("
  SELECT reservations.*, reservations_activities.*
  FROM reservations
  INNER JOIN reservations_activities on (reservations.id = reservation_id)
  WHERE activity_id = 2
  AND DATE(date_start) = '2016-11-09'
  AND date_start is not null
  AND confirmed = 1
  AND deleted = 0
  AND cancelled = 0
  AND is_enquiry = 0
  AND rejected = 0
  LIMIT 10
");

foreach ($rows as $key => $row) {
  print_r($row);
  # code...
}

if($rows === false){
  die("Error");
}

?>
