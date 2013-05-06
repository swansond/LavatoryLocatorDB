<?php
/**
*  Fetches reviews for a specific lavatory in "pages."
*  Accepts GET parameters.
*  Returns reviews as JSON.
*  @author Aasav Prakash
*/

// The number of reviews per page of results
$PAGE_SIZE = 10;

$lid =        $_GET['lid'];
$pageNo =     $_GET['pageNo'];
$sortMethod = $_GET['sortparam'];
$sortDir =    $_GET['direction'];

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

$returnArr = array();
$returnArr['reviews'] = array();
while ($next = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
    $newEntry = array('rid' => $next['review_id'],
        'lid' => $next['lavatory_id'],
        'uid' => $next['user_id'],
        'datetime' => $next['datetime'],
        'review' => $next['review'],
        'rating' => $next['rating'],
        'helpfulness' => $next['helpfulness']);
    array_push($returnArr['reviews'], $newEntry);
}

print json_encode($returnArr);