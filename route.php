<?php
include('config.inc.php');

// Retrieve emergencyLoc, tumtum1Loc and tumtum2Loc
$startx = $_REQUEST['startpointx'];
$starty = $_REQUEST['startpointy'];
$b1x    = $_REQUEST['b1x'];
$b1y    = $_REQUEST['b1y'];
$b2x    = $_REQUEST['b2x'];
$b2y    = $_REQUEST['b2y'];

// Connect to database
$dbcon = pg_connect("dbname=" . PG_DB . " host=" . PG_HOST . " user=" . PG_USER . " password=" . PG_PASSWORD);


//dist to tumtum1
$sql = "
SELECT 
  sum(ST_Length(geom)) as tot_len 
FROM 
pgr_fromAtoB('ways',   
    " . $startx . ", 
    " . $starty . ", 
    " . $b1x . ", 
    " . $b1y . "
  );";


// Perform database query
$query = pg_query($dbcon, $sql);

$res  = pg_fetch_assoc($query);
$sum1 = $res['tot_len'];

echo pg_last_error();

//dist to tumtum2
$sql = "
SELECT 
  sum(ST_Length(geom)) as tot_len 
FROM 
pgr_fromAtoB('ways',   
    " . $startx . ", 
    " . $starty . ", 
    " . $b2x . ", 
    " . $b2y . "
  );";


// Perform database query
$query = pg_query($dbcon, $sql);

$res  = pg_fetch_assoc($query);
$sum2 = $res['tot_len'];
echo pg_last_error();

//decide which tumtum is nearer
if ($sum1 > $sum2) {
    $fx = $b2x;
    $fy = $b2y;
} else {
    $fx = $b1x;
    $fy = $b1y;
}


// finally query for the shortest path

$sql = "
SELECT 
   *, ST_AsGeoJSON(geom) as geojson, ST_Length(geom) as length
FROM 
pgr_fromAtoB('ways',   
    " . $startx . ", 
    " . $starty . ", 
    " . $fx . ", 
    " . $fy . "
  );";


// Perform database query
$query = pg_query($dbcon, $sql);

echo pg_last_error();

// Return route as GeoJSON
$geojson = array(
    'type' => 'FeatureCollection',
    'features' => array()
);
// Add edges to GeoJSON array
while ($edge = pg_fetch_assoc($query)) {
    
    $feature = array(
        'type' => 'Feature',
        'geometry' => json_decode($edge['geojson'], true),
        'crs' => array(
            'type' => 'EPSG',
            'properties' => array(
                'code' => PROJ
            )
            
        ),
        
        'properties' => array(
            'id' => $edge['seq'],
            'length' => $edge['length']
        )
        
    );
    
    
    // Add feature array to feature collection array
    array_push($geojson['features'], $feature);
}

// Close database connection
pg_close($dbcon);

// Return routing result
header('Content-type: application/json', true);
echo json_encode($geojson);


?>
