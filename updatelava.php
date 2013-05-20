<?php
/**
 * This page accepts POST paramaters to submit a request to update a lavatory
 * in the database.
 * @author David Jung
*/

if (!isset($_POST['uid'])
    || !isset($_POST['lid'])
    || !isset($_POST['buildingName'])
    || !isset($_POST['floor'])
    || !isset($_POST['lavaType'])
    || !isset($_POST['longitude'])
    || !isset($_POST['latitude'])) {
    header('HTTP/1.1 400 Invalid Request');
    die('HTTP/1.1 400 Invalid Request: Missing required parameters');
}

$uid = $_POST['uid'];
$lid = $_POST['lid'];
$buildingName = $_POST['buildingName'];
$floor = $_POST['floor'];
$lavaType = $_POST['lavaType'];
$long = $_POST['longitude'];
$lat = $_POST['latitude'];

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

$request = "User ID: $uid; Lavatory ID: $lid; Building: $buildingName; Floor: $floor; Type: $lavaType; Longitude: $long, Latitude: $lat";

// request type and request itself
$query = "INSERT INTO Queue VALUES ('Update lavatory', '$request')";
$result = pg_query($db, $query);
if (!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}
