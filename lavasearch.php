<?php
/**
 * Page that accepts GET parameters to query the Bathroom database and 
 * returns a JSON object with the results.
 * @author David Jung
 */
 
/**
 * @return the string used to connect to the postgres server
 */
function pg_connection_string() {
    return 'dbname=dc9160dninujhs host=ec2-23-21-85-233.compute-1.ama'
         . 'zonaws.com port=5432 user=todxuvhszxmpnh password=fKeepFU'
         . 'ZjdUgrXhifoZsTDsyFG sslmode=require';
}

if (!isset($_GET['bldgName'])
    && !isset($_GET['roomNumber'])
    && !isset($_GET['floor'])
    && !isset($_GET['locationLong'])
    && !isset($_GET['locationLong'])
    && !isset($_GET['maxDist'])
    && !isset($_GET['minRating'])
    && !isset($_GET['minRating'])) {
    header('HTTP/1.1 400 Invalid Request');
    die("HTTP/1.1 400 Invalid Request: no parameters given");
}

$db = pg_connect(pg_connection_string());
if (!$db) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to connect to the server');
}

// Get the query based on our parameters
$query = getQueryString();
$result = pg_query($db, $query);
if (!$result) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to query the server');
}

// Filter out everything that's too far and transform the result
// into an associative array.
$filteredResult = distanceFilter($result);

// Return the result as json.
header('Content-type: application/json');
print json_encode($filteredResult);


/**
 * This function takes the global variables from the GET_ array and returns
 * a suitable query over the lavatory database.
 * @return a suitable query string given the GET_ parameters. 
 */
function getQueryString() {
    $bldgName = $_GET['bldgName'];
    $roomNumber = $_GET['roomNumber'];
    $floor = $_GET['floor'];
    $minRating = $_GET['minRating'];
    $lavatoryType = $_GET['lavaType'];
    
    // First we construct each predicate
    if (isset($bldgName)) {
        $bldgPred = " AND Building.building_name ILIKE '%$bldgName%'";
    }
    if (isset($roomNumber)) {
        $roomPred = " AND Lavatory.room_number = '$roomNumber'";
    }
    if (isset($floor)) {
        $floorPred = " AND Lavatory.floor = '$floor'";
    }
    if (isset($minRating)) {
        $ratingPred = ' AND Lavatory.rating_total / Lavatory.num_reviews '
                    . " >= $minRating";
    }
    if (isset($lavatoryType)) {
        $typePred = " AND Lavatory.lavatory_type = '$lavatoryType'";
    }
    
    // Now we construct the query
    $query = 'SELECT Building.building_name, Lavatory.room_number, '
           . 'Lavatory.latitude, Lavatory.longitude, Lavatory.rating_total, '
           . 'Lavatory.num_reviews, Lavatory.lavatory_type '
           . 'FROM Lavatory, Building '
           . 'WHERE Lavatory.building_id = Building.building_id'
           . $bldgPred . $roomPred . $floorPred . $ratingPred . $typePred . ';';
    return $query;
}

/** 
 * Takes an SQL query result and:
 * (1) Filters anything outside of the max distance specified by the user, if
 *     any, and
 * (2) Transforms the result into an associative array that's able to be
 *     encoded into JSON.
 * @return an associative array as described in (2)
 */
function distanceFilter($result) {
    $locationLong = $_GET['locationLong'];
    $locationLat = $_GET['locationLat'];
    $maxDist = $_GET['maxDist'];
    $returnArr = array();
    $returnArr['lavatories'] = array();
    
    // Fetch the next row as an associative array
    while ($next = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
        $lavaLong = $next['longitude'];
        $lavaLat = $next['latitude'];
        
        $distance = getDistance(deg2rad($locationLat), deg2rad($locationLong),
            deg2rad($lavaLat), deg2rad($lavaLong));
            
        printf("distance: $distance\n");
        printf("lavatory id: " . $next['lavatory_id'] . "\n");
        
        if ($distance <= $maxDist) {
            // Then we can add this row to the results
            $newEntry = array('lid' => $next['lavatory_id'],
                'building' => $next['building_name'],
                'room' => $next['room_number'],
                'distance' => $distance,
                'avgRating' => $next['rating_total'] / $next['num_reviews'],
                'reviews' => $next['num_reviews'],
                'type' => $next['lavatory_type']);
            array_push($returnArr['lavatories'], $newEntry);
        }
    }
    return $returnArr;
}

/**
 * Returns the great circle distance from the source coordinates to the
 * target coordinates.
 * Uses the wikipedia-prescribed "special case of the Vincenty formula"
 * Argument coordinates must be in radians.
 * @return the distance from src to target in meters.
 */
function getDistance($srcLat, $srcLong, $targetLat, $targetLong) {
    $EARTH_RAD = 6371000;
    $deltaLong = abs($srcLong - $targetLong);
    
    $x = sqrt(
        pow((cos($targetLat) * sin($deltaLong)), 2) +
        pow(
            ((cos($srcLat) * sin($targetLat)) - 
            (sin($srcLat) * cos($targetLat) * cos($deltaLong))), 2
        )
    );
    $y = sqrt(
        (sin($srcLat) * sin($targetLat)) +
        (cos($srcLat) * cos($targetLat) * cos($deltaLat))
    );
    return atan2($num, $denom) * $EARTH_RAD;
}