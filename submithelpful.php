<?php
/**
*  Page accepts POST parameters to record a user's helpfulness submission.
*  @author Aasav Prakash
*/


if(!ISSET($_POST['reviewId']) || !ISSET($_POST['uid']) ||
    !ISSET($_POST['helpful'])) {
    header('HTTP/1.1 400 Invalid Request');
    die("HTTP/1.1 400 Invalid Request: Missing required parameters");
}

$review = pg_escape_string($_POST['reviewId']);
$uid = pg_escape_string($_POST['uid']);
$vote = pg_escape_string($_POST['helpful']); // must be 1 or -1

function pg_connection_string() {
    return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com 
    port=5432 user=todxuvhszxmpnh 
    password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}

$db = pg_connect(pg_connection_string());
if (!$db) {
   header('HTTP/1.1 501 Server Error');
   die('HTTP/1.1 501 Server Error: unable to connect to the server');
}

// Check if user is in database already
// We do this first iso that in case of an error, nothing gets changed
$acctQuery = "SELECT * FROM Account WHERE user_id=$uid";
$acctResult = pg_query($db, $acctQuery);
if (!$acctResult) {
    header('HTTP/1.1 500 Server Check Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

// If there is no account entry, add one
if (pg_num_rows($acctResult) == 0) {
    if (!ISSET($_POST['username'])) {
        header('HTTP/1.1 400 Invalid Request');
        die("HTTP/1.1 400 Invalid Request: Missing required parameter: username");
    }
    $username = pg_escape_string($_POST['username']);
    $userQuery = "INSERT INTO Account VALUES ($uid, '$username')";
    $userResult = pg_query($db, $userQuery);
    if (!$userResult) {
        header('HTTP/1.1 500 Server Check Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }
}


// First we have to get the current helpfulness for the review
$query = "SELECT helpfulness, total_votes FROM Review WHERE review_id=$review";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 502 Server Error');
    die('HTTP/1.1 502 Server Error: unable to query the server');
}

$resultRow = pg_fetch_row($result);

if (!$resultRow) {
    die();
}

$helpfulness = $resultRow[0];
$total_votes = $resultRow[1];

if ($vote == -1) {
    $vote = 0;
}

// Now we update it
$helpfulness += $vote;
$total_votes++;

// Set up the string to update the database
$query = "UPDATE Review SET helpfulness=$helpfulness, total_votes=$total_votes
          WHERE review_id=$review";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 503 Server Error');
    die('HTTP/1.1 503 Server Error: unable to query the server');
}


// Finally, update the Helpful table to show 
// this user has marked the review
if ($vote == 1) {
    $vote = "true";
} else {
    $vote = "false";
}
$query = "INSERT INTO helpful VALUES ($uid, $review, '$vote')";
$result = pg_query($db, $query);
if(!$result) {
    header('HTTP/1.1 504 Server Error');
    die('HTTP/1.1 504 Server Error: unable to query the server');
}
