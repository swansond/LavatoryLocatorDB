<?php
/**
*  This page accepts POST paramaters to add a bathroom to the database.
*  @author Aasav Prakash
*/

$uid = $_POST['uid'];
$buildingName = $_POST['buildingName'];
$floor = $_POST['floor'];
$lavaType = $_POST['lavaType'];
$long = $_POST['longitude'];
$lat = $_POST['latitude'];

if (!$uid || !$buildingName || !$floor || !$lavaType || !$long || !$lat) {
    header('HTTP/1.1 400 Invalid Request');
    die('HTTP/1.1 400 Invalid Request: Missing required parameters');
}

/**
 * @return the string used to connect to the postgres server
 */
function pg_connection_string() {
    return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com
    port=5432 user=todxuvhszxmpnh
    password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}

$db = pg_connect(pg_connection_string());
if (!$db) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to connect to the server');
}

$request = "User ID: $uid; Building: $buildingName; Floor: $floor; Type: $lavaType; Longitude: $long, Latitude: $lat";

// request type and request itself
$query = "INSERT INTO Queue VALUES ("Add lavatory", $request)";
$result = pg_query($db, $query);
if (!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}