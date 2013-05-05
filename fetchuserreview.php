<?php
/**
*  This page accepts POST parameters and returns the user's review 
*  for the bathroom if one exists; nothing if one does not exist.
*  Returns reviews in JSON format. 
*  @author Aasav Prakash
*/

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

// Check if the user already has a review
$checkQuery = "SELECT * FROM Review WHERE lavatory_id='$lID' AND user_id='$userID'";
$checkResult = pg_query($db, $checkQuery);
if (!$checkResult) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

if (pg_num_rows($checkResult) != 0) { 
    // User has a review; return it
    $reviewRow = pg_fetch_row($checkResult);
    print json_encode($reviewRow);
} // If one does not exist, we return nothing.