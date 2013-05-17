<?php
/**
 * Page that takes a user location and returns the lavatory closest to that
 * location.
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

// Make sure the user location has been sent
if (!isset($_GET['locationLat']) || !isset($_GET['locationLong'])) {
    header('HTTP/1.1 400 Invalid Request');
    die("HTTP/1.1 400 Invalid Request: user location not given");
}

// Establish our connection to the server.
$db = pg_connect(pg_connection_string());
if (!$db) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to connect to the server');
}

// Query the database for all the bathrooms
$query = getQueryString();
$result = pg_query($db, $query);
if (!$result) {
   header('HTTP/1.1 500 Server Error');
   die('HTTP/1.1 500 Server Error: unable to query the server');
}

// Get the bathroom closest to the user
$closestLava = getClosestLava($result);

// Return the result as json.
header('Content-type: application/json');
print json_encode($closestLava);

/**
 * This function returns the query string used for this request.
 * We hardcode it because we always want to examine all bathrooms.
 */
function getQueryString() {
    return 'SELECT Lavatory.lavatory_id, Building.building_name, '
           . 'Lavatory.room_number, Lavatory.latitude, Lavatory.longitude, '
           . 'Lavatory.rating_total, Lavatory.num_reviews, '
           . 'Lavatory.lavatory_type '
           . 'FROM Lavatory, Building '
           . 'WHERE Lavatory.building_id = Building.building_id;';
}

/** 
 * Takes an SQL query result and returns the lavatory closest to the
 * user.
 * @return the lavatory closest to the user, in an array.
 */
function getClosestLava($result) {
    $locationLong = $_GET['locationLong'];
    $locationLat = $_GET['locationLat'];
    
    // Stores each lavatory and its distance
    $lavatories = array();
    
    // Will eventually store the closest lavatory
    $closestLava = array();
    
    // Used to match the output of lavasearch
    $returnArr = array();
    $returnArr['lavatories'] = array();
    
    // Populate the lavatories array with the query results
    while ($next = pg_fetch_array($result, NULL, PGSQL_ASSOC)) {
        $lavaLong = $next['longitude'];
        $lavaLat = $next['latitude'];
        
        // Calculate the lavatory's distance from the user
        $distance = getDistance(deg2rad($locationLat), deg2rad($locationLong),
            deg2rad($lavaLat), deg2rad($lavaLong));
        
        // Store this lavatory and its distance in lavatories
        $newEntry = array('lid' => $next['lavatory_id'],
                'building' => $next['building_name'],
                'room' => $next['room_number'],
                'distance' => $distance,
                'reviews' => $next['num_reviews'],
                'type' => $next['lavatory_type'],
                'latitude' => $next['latitude'],
                'longitude' => $next['longitude']);

        // Safely calculate the average rating
        if ($next['num_reviews'] == 0) {
            $newEntry['avgRating'] = 0;
        } else {
            $newEntry['avgRating'] = $next['rating_total']
                    / $next['num_reviews'];
        }

        array_push($lavatories, $newEntry);
    }
    
    // Iterate through lavatories and find the lavatory with minimum distance
    $closestLava = $lavatories[0];
    foreach ($lavatories as $lavatory) {
        if ($closestLava['distance'] > $lavatory['distance']) {
            $closestLava = $lavatory;
        }
    }

    array_push($returnArr['lavatories'], $closestLava);
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
        (cos($srcLat) * cos($targetLat) * cos($deltaLong))
    );
    
    return atan2($x, $y) * $EARTH_RAD;
}