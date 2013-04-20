<?php
# This function reads your DATABASE_URL configuration automatically set by Heroku
# the return value is a string that will work with pg_connect
function pg_connection_string() {
	return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com port=5432 user=todxuvhszxmpnh password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}

$db = pg_connect(pg_connection_string());
if (!$db) {
   die("Database connection error.");
}

$result = pg_query($db, 'select * from bathroom');
$toilets = array();
while ($row = pg_fetch_row($result)) {
	$toilets[] = $row;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>LavatoryLocator Submissions</title>
		<link rel='stylesheet' type='text/css' href='subs.css' />
	</head>
	<body>
		<div id='main'>
			<h1>LavatoryLocator Submissions</h1>
			<form action='submitReview.php' method='POST'>
				<div id='form'>
					<p> Choose a bathroom to review:
						<select name='toilet'>
							<?php
							foreach ($toilets as $row) { ?>
								<option value='<?= $row[0] ?>'><?= "$row[2] Floor $row[3]" ?></option>
							<?php } ?>
						</select>
					</p>
					<p> Rate the bathroom out of 5:
						<select> name='rating'>
							<option>1</option>
							<option>2</option>
							<option>3</option>
							<option>4</option>
							<option>5</option>
						</select>
					</p>
					<p>
						<textarea name='review' rows='20' cols='50'>Type your comments here.</textarea>
					</p>
					<input type='submit' value="Submit">
				</div>
			</form>
		</div>
	</body>
</html>