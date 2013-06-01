<?php
/**
*  Page accepts POST parameters to update a user's review, 
*  or add a review if one does not exist.
*  @author Aasav Prakash
*/

$lid = pg_escape_string($_POST['lid']);
$rating = pg_escape_string($_POST['rating']);
$review = pg_escape_string($_POST['review']);
$userId = pg_escape_string($_POST['uid']);

if (!ISSET($_POST['lid']) || !ISSET($_POST['rating']) || 
    !ISSET($_POST['uid'])) {
    header('HTTP/1.1 400 Invalid Request');
    die('HTTP/1.1 400 Invalid Request: Missing required parameters. lid, rating, and uid required');
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
   header('HTTP/1.1 500 Server Connect Error');
   die('HTTP/1.1 500 Server Error: unable to connect to the server');
}

// Check if user is in database already
$acctQuery = "SELECT * FROM Account WHERE user_id=$userId";
$acctResult = pg_query($db, $acctQuery);
if (!$acctResult) {
    header('HTTP/1.1 500 Server Check Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

// If there is no account entry, add one
if (pg_num_rows($acctResult) == 0) {
    $userQuery = "INSERT INTO Account VALUES ($userId)";
    $userResult = pg_query($db, $userQuery);
    if (!$userResult) {
        header('HTTP/1.1 500 Server Check Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }
}



// Check if the user already has a review
$checkQuery = "SELECT rating FROM Review WHERE lavatory_id=$lid AND user_id=$userId";
$checkResult = pg_query($db, $checkQuery);
if (!$checkResult) {
    header('HTTP/1.1 500 Server Check Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

if (pg_num_rows($checkResult) == 0) {
    // User does not have review; add a new one
    $query = "INSERT INTO 
              Review (lavatory_id, user_id, datetime, review, 
                      rating, helpfulness, total_votes)
              VALUES ($lid, $userId, NOW(), '$review', $rating, 0, 0)";
    $result = pg_query($db, $query);
    if (!$result) {
        header('HTTP/1.1 500 Server Insert Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }
    // Now we get the current review information from the Lavatory table
    $lavInfo = fetchLavatoryInfo($db, $lid);
    $lavNumRevs = $lavInfo[0];
    $lavTotalRating = $lavInfo[1];
    // Update the info
    $lavNumRevs++;
    $lavTotalRating += $rating;
    $lavUpdateQuery = "UPDATE Lavatory 
                       SET num_reviews=$lavNumRevs, rating_total=$lavTotalRating
                       WHERE lavatory_id=$lid";
    $updateResult = pg_query($db, $lavUpdateQuery);
    if (!$updateResult) {
      header('HTTP/1.1 500 Server Insert Update Error');
      die('HTTP/1.1 500 Server Error: unable to query the server');
    }
} else {
    // User has an existing review; update it
    // But first we need the value of the old rating
    $oldRatingRow = pg_fetch_row($checkResult);
    $oldRating = $oldRatingRow[0];
    // Now update the Review table
    $query = "UPDATE Review 
              SET datetime=NOW(), review='$review', rating=$rating, helpfulness=0
              WHERE lavatory_id=$lid AND user_id=$userId";
    $result = pg_query($db, $query);
    if (!$result) {
	header('HTTP/1.1 500 Server Update Error');
        die('HTTP/1.1 500 Server Error: unable to query the server');
    }
    // Fetch and Update the rating in the Lavatory table
    // Now we get the current review information from the Lavatory table
    $lavInfo = fetchLavatoryInfo($db, $lid);
    $lavNumRevs = $lavInfo[0];
    $lavTotalRating = $lavInfo[1];
    // Update the lavatory entry
    $lavTotalRating = $lavTotalRating - $oldRating + $rating;
    $lavUpdateQuery = "UPDATE Lavatory 
                       SET rating_total=$lavTotalRating
                       WHERE lavatory_id=$lid";
    $updateResult = pg_query($db, $lavUpdateQuery);
    if (!$updateResult) {
      header('HTTP/1.1 500 Server Update Query Error');
      die('HTTP/1.1 500 Server Error: unable to query the server');
    }
}

/**
*  Function to get the info for a specific lavatory
*  @param db The postgre database object to use for the query
*  @param lid The lavatory ID to use  
*  @return A row entry holding the number of reviews and total rating
*/
function fetchLavatoryInfo($db, $lid) {
  $lavQuery = "SELECT num_reviews, rating_total FROM Lavatory 
               WHERE lavatory_id=$lid";
  $lavResult = pg_query($db, $lavQuery);
  if (!$lavResult) {
      header('HTTP/1.1 500 Server Error');
      die('HTTP/1.1 500 Server Error: unable to query the server');
  }
  $lavInfo = pg_fetch_row($lavResult);
  return $lavInfo;
}
