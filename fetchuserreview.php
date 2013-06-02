<?php
/**
*  This page accepts POST parameters and returns the user's review 
*  for the bathroom if one exists; nothing if one does not exist.
*  Returns reviews in JSON format. 
*  @author Aasav Prakash
*/


$lid = pg_escape_string($_GET['lid']);
$uid = pg_escape_string($_GET['uid']);

if (!ISSET($_GET['lid']) || !ISSET($_GET['uid'])) {
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

// Check if the user already has a review
$checkQuery = "SELECT * FROM Review WHERE lavatory_id=$lid AND user_id=$uid";
$checkResult = pg_query($db, $checkQuery);
if (!$checkResult) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

if (pg_num_rows($checkResult) != 0) { 
    // User has a review; return it
    $userReview = pg_fetch_array($checkResult, NULL, PGSQL_ASSOC);
    
    $returnJson = array('rid' => $userReview['review_id'],
        'lid' => $userReview['lavatory_id'],
        'uid' => $userReview['user_id'],
        'datetime' => $userReview['datetime'],
        'review' => $userReview['review'],
        'rating' => $userReview['rating'],
        'helpfulness' => $userReview['helpfulness']);
    
    header('Content-type: application/json');
    print json_encode($returnJson);
} // If one does not exist, we return nothing.