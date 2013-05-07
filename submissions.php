<?php
/**
*  This page allows the user to select a bathroom and submit a review for it.
*  @author Aasav Prakash
*/

/**
 * @return the string used to connect to the postgres server
 */
function pg_connection_string() {
	return "dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.amazonaws.com port=5432 user=todxuvhszxmpnh password=fKeepFUZjdUgrXhifoZsTDsyFG sslmode=require";
}

$db = pg_connect(pg_connection_string());
if (!$db) {
	header('HTTP/1.1 500 Server Error');
	die('HTTP/1.1 500 Server Error: unable to connect to the server');
}

$result = pg_query($db, 'select * from Lavatory');
$lavs = array();
while ($row = pg_fetch_row($result)) {
	$lavs[] = $row;
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
			<form action='submitreview.php' method='POST'>
				<div id='form'>
					<p> Choose a bathroom to review:
						<select name='lid'>
							<?php
							// Place the lavatories into the dropdown
							foreach ($lavs as $row) { 
								$bid = $row[2];
								// Since building names are not stored in Lavatory table, 
								// we grab it from the Building table
								$bldgQuery = "select building_name from Building where building_id=$bid";
								$bldgRow = pg_fetch_row(pg_query($db, $bldgQuery));
								$bldg = $bldgRow[0];
								$lid = $row[0];
								$floor = $row[4];
								?>
								<option value='<?= $lid ?>'><?= "$floor Floor $bldg" ?></option>
							<?php } ?>
						</select>
					</p>
					<p> Rate the bathroom out of 5:
						<select name='rating'>
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
					<input type='hidden' name='uid' value='-1'>
					<input type='submit' value="Submit">
				</div>
			</form>
			<form action='addlava.php' method='POST'>
				<div>
					<p>Add a lavatory!
						<input type='hidden' name='uid' value='-1'>
						<input type='text' name='buildingName' value='Building Name'>
						<input type='text' name='roomNumber' value='Room Number'>
						<input type='text' name='floor' value='Floor'>
						<input type='hidden' name='lavaType' value='A'>
						<input type='hidden' name='longitude' value='42.42'>
						<input type='hidden' name='latitude' value='42.42'>
					</p>
					<input type='submit' value='Submit Lavatory'>
				</div>
			</form>
		</div>
	</body>
</html>
