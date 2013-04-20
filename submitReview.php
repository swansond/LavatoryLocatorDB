<?php
# Page accepts POST parameters to add a review to the database

$toiletID = $_POST['toilet'];
$rating = $_POST['rating'];
$review = pg_escape_string($_POST['review']);
$userID = $_POST['userID'];

if(!$toiletID || !$rating || !$review) {
	die('Invalid parameters');
}

function pg_connection_string() {
	return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com port=5432 user=todxuvhszxmpnh password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}

$db = pg_connect(pg_connection_string());
if (!$db) {
   die('Database connection error');
}

$now = new DateTime().getTimestamp();
$query = "INSERT INTO review VALUES(1, $toiletID, $userID, $now, $review, $rating, 0)";
$result = pg_query($db, $query);
if(!$result) {
	die('SQL Query error');
}
?>