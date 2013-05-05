<?php
/**
*  Page accepts POST parameters to update a user's review, 
*  or add a review if one does not exist.
*  @author Aasav Prakash
*/

$lID = $_POST['lid'];
$rating = $_POST['rating'];
$review = pg_escape_string($_POST['review']);
$userId = $_POST['uid'];
$now = new DateTime().getTimestamp();

if (!$lID || !$rating || !$review || !$userId) {
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
$checkQuery = "SELECT * FROM Review WHERE lavatory_id='$lID' AND user_id='$userId'";
$checkResult = pg_query($db, $checkQuery);
if (!$checkResult) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

if (pg_num_rows($checkResult) == 0) {
    // User does not have review; add a new one
    $query = "INSERT INTO Review (lavatory_id, user_id, datetime, review, 
                                  rating, helpfulness)
              VALUES($lID, $userId, $now, $review, $rating, 0)";
    $result = pg_query($db, $query);
    if (!$result) {
        header('HTTP/1.1 500 Server Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }    
} else {
    // User has an existing review; update it
    $query = "UPDATE Review SET datetime='$now', review='$review', rating='$rating'
              WHERE lavatory_id='$lID' AND user_id='$userId'";
    $result = pg_query($db, $query);
    if (!$result) {
        header('HTTP/1.1 500 Server Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }
}
