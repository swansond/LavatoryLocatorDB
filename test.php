<?php
# This function reads your DATABASE_URL configuration automatically set by Heroku
# the return value is a string that will work with pg_connect
function pg_connection_string() {
	return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com port=5432 user=todxuvhszxmpnh password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}
$query = "select * from team;";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$query = $_POST["query"];
}
# Establish db connection
$db = pg_connect(pg_connection_string());
if (!$db) {
   echo "Database connection error.";
   exit;
}
$result = pg_query($db, $query);

$myarray = array();
while ($row = pg_fetch_row($result)) {
	$myarray[] = $row;
}

echo json_encode($myarray);
?>