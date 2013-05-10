<?php
/**
*  Page accepts POST parameters to record a user's helpfulness submission.
*  @author Aasav Prakash
*/

$review = $_POST['reviewId'];
$uid = $_POST['uid'];

if(!$reviewID || !$uid) {
    header('HTTP/1.1 400 Invalid Request');
    die("HTTP/1.1 400 Invalid Request: no parameters given");
}

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

// First we have to get the current helpfulness for the review
$query = "SELECT helpfulness FROM Review WHERE review_id=$review";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}
$helpfulnessRow = pg_fetch_row($result)
$helpfulness = $helpfulnessRow[0];

// Now we update it
$helpfulness++;
$query = "UPDATE Review SET helpfulness=$helpfulness WHERE review_id=$review";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

// Finally, update the Helpful table to show 
// this user has marked the review helpful
$query = "INSERT INTO Helpful VALUES ($uid, $review, true)";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}