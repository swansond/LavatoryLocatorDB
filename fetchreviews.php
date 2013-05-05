<?php
/**
*  Fetches reviews for a specific lavatory in "pages."
*  Reviews returned as JSON.
*  @author Aasav Prakash
*/

// The number of reviews per page of results
$PAGE_SIZE = 10;

$lid =        $_POST['lid'];
$pageNo =     $_POST['pageNo'];
$sortMethod = $_POST['sortparam'];
$sortDir =    $_POST['direction'];

if (!$lid || !$pageNo || !$sortMethod || !$sortDir) {
    header('HTTP/1.1 400 Invalid Request');
    die('HTTP/1.1 400 Invalid Request: Missing required parameters');
}

$querySortMethod = 'helpfulness'; // Default sort method is helpfulness
if ($sortMethod == 'date') {
    $querySortMethod = 'datetime';
} elseif ($sortMethod == 'rating') {
    $querySortMethod = 'rating';
}

$ordering = 'DESC'; // Default order is descending
if ($sortDir == 'ascending') {
    $ordering = 'ASC';
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

$queryOffset = ($pageNo - 1) * $PAGE_SIZE;

$query = "SELECT * FROM Review ORDER BY $querySortMethod $ordering 
          LIMIT $PAGE_SIZE OFFSET $queryOffset";
$result = pg_query($db, $query);
if (!$result) {
    header('HTTP/1.1 500 Server Error');
    die('HTTP/1.1 500 Server Error: unable to query the server');
}

$reviews = array();
while ($row = pg_fetch_row($result)) {
    $reviews[] = $row;
}

print json_encode($reviews);