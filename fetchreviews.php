<?php
/**
*  Fetches reviews for a specific lavatory in "pages."
*  Accepts GET parameters.
*  Returns reviews as JSON.
*  @author Aasav Prakash
*/

// The number of reviews per page of results
$PAGE_SIZE = 10;

$lid =        pg_escape_string($_GET['lid']);
$pageNo =     pg_escape_string($_GET['pageNo']);
$sortMethod = pg_escape_string($_GET['sortparam']);
$sortDir =    pg_escape_string($_GET['direction']);
$uid =        pg_escape_string($_GET['uid']);

if (!ISSET($_GET['lid']) || !ISSET($_GET['pageNo']) ||
    !ISSET($_GET['sortparam']) || !ISSET($_GET['direction'])) {
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

$query = "SELECT * FROM Review, Account 
          WHERE Review.lavatory_id=$lid AND Account.user_id=Review.user_id
          ORDER BY $querySortMethod $ordering 
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
        'username' => $next['username'],
        'datetime' => $next['datetime'],
        'review' => $next['review'],
        'rating' => $next['rating'],
        'helpfulness' => $next['helpfulness'],
        'totalvotes' => $next['total_votes']);
    // If a user ID was supplied, see if they marked this review (un)helpful
    if (isset($_GET['uid'])) {
        $rid = $newEntry['rid'];
        $query = "SELECT helpfulness FROM helpful
                  WHERE user_id=$uid AND review_id=$rid";
        $userResult = pg_query($db, $query);
        if (!$userResult) {
            header('HTTP/1.1 500 Server Error');
            die('HTTP/1.1 500 Server Error: unable to query the server');
        }
        $userArr = pg_fetch_assoc($userResult);
        $userVote = 0;
        if (count($userArr) != 0) {
            if ($userArr['helpfulness']) {
                $userVote = 1;
          } else {
                $uservote = -1;
          }
        }
        $newEntry['uservote'] = $userVote;
    }
    array_push($returnArr['reviews'], $newEntry);
}

header('Content-type: application/json');
print json_encode($returnArr);
