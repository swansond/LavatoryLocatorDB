<?php
# This function reads your DATABASE_URL configuration automatically set by Heroku
# the return value is a string that will work with pg_connect
function pg_connection_string() {
	return "postgres://todxuvhszxmpnh:fKeepFUZjdUgrXhifoZsTDsyFG@ec2-23-21-85-233.compute-1.amazonaws.com:5432/dc9160dninujhs";
}
 
# Establish db connection
$db = pg_connect(pg_connection_string());
if (!$db) {
   echo "Database connection error."
   exit;
}
 
$result = pg_query($db, "SELECT statement goes here");
?>
<html>
	<head>
	</head>
	<body>
		<h1>Database page</h1>
	</body>
</html>
