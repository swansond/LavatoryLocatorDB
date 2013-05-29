<?php
/**
*  This page accepts POST paramaters to submit a request to delete a bathroom.
*  @author Aasav Prakash
*/

$uid = $_POST['uid'];
$lavatory = $_POST['lavatoryid'];

if (!$lavatory) {
    header('HTTP/1.1 400 Invalid Request');
    die('HTTP/1.1 400 Invalid Request: Missing required parameter: lavatoryid');
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

$request = "Lavatory ID: $lavatoryid";
if ($uid) {
    $request .= "; User ID: $uid";
}

// request type and request itself
$query = "INSERT INTO Queue VALUES ('Delete lavatory', '$request')";
$result = pg_query($db, $query);
if (!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: Server Query Failed');
}
