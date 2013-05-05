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

// Definitions for parameter names
$BLDG_NAME = 'bldgName';
$ROOM_NAME = 'roomNumber';
$FLOOR_NUM = 'floor';
$LOC_LONG = 'locationLong';
$LOC_LAT = 'locationLat';
$MAX_DIST = 'maxDist';
$MIN_RATING = 'minRating';
$BATH_TYPE = 'bathroomType';

// Definitions for database names
$BATH_TYPE_DB = 'bathroom_type';
$BLDG_ID_DB = 'building_id';
$BLDG_NAME_DB = 'building_name';
$ROOM_NAME_DB = 'room_number';
$FLOOR_NUM_DB = 'floor';
$NUM_REVS_DB = 'num_reviews';
$RATE_TOTAL_DB = 'rating_total';
$LONG_DB = 'longitude';
$LAT_DB = 'latitude';

// Earth's radius, in meters: used for calculating distances
$EARTH_RAD = 6371000;

if (!isset($_GET[$BLDG_NAME])
    && !isset($_GET[$ROOM_NAME])
    && !isset($_GET[$FLOOR_NUM])
    && !isset($_GET[$LOC_LONG])
    && !isset($_GET[$LOC_LAT])
    && !isset($_GET[$MAX_DIST])
    && !isset($_GET[$MIN_RATING])
    && !isset($_GET[$BATH_TYPE])) {
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
$result = pg_query($db, pg_escape_string($db, $query));
if (!$result) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to query the server' .
       "\nQuery: $query");
}

// Filter out everything that's too far and transform the result
// into an associative array.
$filteredResult = distanceFilter($result);

// Return the result as json.
header('Content-type: application/json');
print json_encode($filteredResult);


/**
 * This function takes the global variables from the GET_ array and returns
 * a suitable query over the bathroom database.
 * @return a suitable query string given the GET_ parameters. 
 */
function getQueryString() {
    $bldgName = $_GET[$BLDG_NAME];
    $roomNumber = $_GET[$ROOM_NUM];
    $floor = $_GET[$FLOOR_NUM];
    $minRating = $_GET[$MIN_RATING];
    $bathroomType = $_GET[$BATH_TYPE];
    
    // First we construct each predicate
    if (isset($bldgName)) {
        $bldgPred = " AND Bathroom.$BLDG_ID_DB = Building.$BLDG_ID_DB"
                  . " AND Building.$BLDG_NAME_DB ILIKE %$bldgName%";
    }
    if (isset($roomNumber)) {
        $roomPred = " AND Bathroom.$ROOM_NAME_DB = $roomNumber";
    }
    if (isset($floor)) {
        $floorPred = " AND Bathroom.$FLOOR_NUM_DB = $floor";
    }
    if (isset($minRating)) {
        $ratingPred = " AND Bathroom.$RATE_TOTAL_DB / Bathroom.$NUM_REVS_DB"
                    . " >= $minRating";
    }
    if (isset($bathroomType)) {
        $typePred = " AND Bathroom.$BATH_TYPE_DB = $bathroomType";
    }
    
    // Now we construct the query
    // Must join with Building if bldgPred is specified
    $query = "SELECT $BLDG_NAME_DB, $ROOM_NAME_DB, $LAT_DB, $LONG_DB, "
           . "$RATE_TOTAL_DB, $NUM_REVS_DB, $BATH_TYPE_DB "
           . 'FROM Bathroom, Building '
           . "WHERE Bathroom.$BLDG_ID_DB = Building.$BLDG_ID_DB"
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
    $locationLong = $_GET[LOC_LONG];
    $locationLat = $_GET[LOC_LAT];
    $maxDist = $_GET[MAX_DIST];
    $returnArr = array();
    $returnArr['lavatories'] = array();
    
    // Fetch the next row as an associative array
    while ($next = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
        $bathLong = $next['longitude'];
        $bathLat = $next['latitude'];
        
        $distance = getDistance(deg2rad($locationLat), deg2rad($locationLong),
            deg2rad($bathLat), deg2rad($bathLong));
            
        if ($distance <= $maxDist) {
            // Then we can add this row to the results
            $newEntry = array('building' => $next['building_name'],
                'room' => $next['room_number'],
                'distance' => $distance,
                'avgRating' => $next['rating_total'] / $next['num_reviews'],
                'reviews' => $next['num_reviews'],
                'type' => $next['bathroom_type']);
            array_push($returnArr['lavatories'], $newEntry);
            $returnArr['lavatories'][] = array();
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
    return atan2($num, $denom) * EARTH_RAD;
}