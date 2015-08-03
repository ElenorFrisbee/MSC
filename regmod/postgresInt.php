<?php

// Show all information, defaults to INFO_ALL
//phpinfo();



/*
* Define PostgreSQL database server connect parameters.
*/            

define('PGHOST','localhost');
define('PGPORT',5432);
define('PGDATABASE','MYDB');
define('PGUSER', 'MYUSER');
define('PGPASSWORD', 'MYPASSWORD');
define('PGCLIENTENCODING','UNICODE');
define('ERROR_ON_CONNECT_FAILED','Sorry, can not connect the database server now!');

/*
* Merge connect string and connect db server with default parameters.
*/

$fyear = $_GET['year'];
$fmonth = $_GET['month'];
$fmode = $_GET['mode'];
$input_areaView = $_GET['areaView'];


$dbconn = pg_connect('host=' . PGHOST . ' port=' . PGPORT . ' dbname=' . PGDATABASE . ' user=' . PGUSER . ' password=' . PGPASSWORD)  or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());
header('Content-Type: application/json');

if($fmode == 1){
    // get all years for select
    // get year and month as json 
    $query = 'SELECT Distinct year, month from tambora_temperature_monthly WHERE value_idx != 0 ORDER BY year, month ASC;'; 
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    $resres = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['year'];
        $val = $line['month'];
        if(empty($resres[$key])) $resres[$key] = array($val);
        else $resres[$key][] = $val;
    }   

    echo $_GET['callback'] . '(' . json_encode($resres) .')';


} elseif($fmode == 2){

    // get reconstruct data
    $query = "SELECT * FROM temperature_monthly_recon WHERE year = ". $fyear. ' AND month = '.$fmonth.';';
    //$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    $resres = array();
    $reconDat = array();
    $line = pg_fetch_array($result, null, PGSQL_ASSOC) ;

    foreach ($line as $key => $col_value) {
        $reconDat += array($key => $col_value);
    }
    $resres['reconData'] = $reconDat;

    // get idx point data
    $idxPDat = array();
    $index = 0;
    $query = "SELECT * FROM  tambora_temperature_monthly WHERE year = ". $fyear. ' AND month = '.$fmonth.';';
    //$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){
        $idxPDatLine = array(); 
        foreach ($line as $key => $col_value) {
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $index++;
    }

    $resres['idxPDat'] = $idxPDat;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';


} elseif($fmode==3 && intval($input_areaView) == 1){

    // get idx point data
    $resres = array();
    $idxPDat = array();
    $index = 0;


    $query = "SELECT location, idx, lon, lat, text, event_id, lat_info, lon_info FROM tambora_temperature_monthly WHERE year = ". $fyear. ' AND month = '.$fmonth.';';
    //$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){
        $idxPDatLine = array(); 
        $eventId = array();
        foreach ($line as $key => $col_value) {
            if($key === "event_id"){
                $eventId = $col_value;
            }
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $eventIds[$index] = $eventId;
        $index++;
    }
    if($eventIds == ''){
        $resres['idxPDat']='';
        echo $_GET['callback'] . '(' . json_encode( $resres) .')';
        return;
    }  
    // get cru stats from event ids

    $input_evid = implode(",",$eventIds);
    $query = "SELECT AA.event_id, (ST_SUMMARYSTATS(AA.rast)).mean - (ST_SUMMARYSTATS(ST_INTERSECTION(BB.rast,AA.rast))).mean AS cru_diff_mean FROM temperature_monthly_recon_single AS AA, temperature_cru_mean AS BB WHERE AA.event_id IN(".$input_evid.") AND BB.month = ".$fmonth.";";

    //$query = "SELECT event_id, cru_diff_mean FROM temperatureStats WHERE event_id IN(".$input_evid.");";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $cruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $key = $line['event_id'];
        $val = round($line['cru_diff_mean'],2);
        if(empty($stats[$key])) $cruStats[$key] = array($val);
        else $cruStats[$key] = $val;
    }   

    // get temp stats from weather station data

    $input_evid = implode(",",$eventIds);
    $query = "SELECT event_id, id,  temp_recon - temperature as station_offset FROM temp_stations_relevant WHERE event_id IN(".$input_evid.");";

    $query="SELECT 
    AA.event_id, avg(ST_Value(AA.rast, BB.geom) - BB.temperature) as station_offset FROM (
    SELECT rast, event_id FROM temperature_monthly_recon_single WHERE event_id IN(".$input_evid.")
    ) as AA,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ". $fyear. " AND month = ".$fmonth."
    ) as BB
    WHERE ST_Intersects(AA.rast, BB.geom,1)
    GROUP BY event_id;";

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){  
        $key1 = $line['event_id'];
        $val = round($line['station_offset'],2);
        $stationStats[$key1]= $val;  
    }   


    $resres['idxPDat'] = $idxPDat;
    $resres['cruStats'] = $cruStats;
    $resres['stationStats'] = $stationStats;



    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}elseif($fmode == 3 && intval($input_areaView) != 1){

    // get idx point data
    $resres = array();
    $idxPDat = array();
    $index = 0;
    $input_areaView = json_decode($input_areaView, true);

    $query = "SELECT * FROM  tambora_temperature_monthly WHERE year = ". $fyear. ' AND month = '.$fmonth.' AND ST_Intersects(geom, ST_MakeEnvelope('.$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].', 4326));';
    //$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){
        $idxPDatLine = array(); 
        $eventId = array();
        foreach ($line as $key => $col_value) {
            if($key === "event_id"){
                $eventId = $col_value;
            }
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $eventIds[$index] = $eventId;
        $index++;
    }

    if($eventIds == ''){
        $resres['idxPDat']='';
        echo $_GET['callback'] . '(' . json_encode( $resres) .')';
        return;
    }  
    // get cru stats from event ids


    $input_evid = implode(",",$eventIds);
    $query = "SELECT AA.event_id, (ST_SUMMARYSTATS(AA.rast)).mean - (ST_SUMMARYSTATS(ST_INTERSECTION(BB.rast,AA.rast))).mean AS cru_diff_mean FROM temperature_monthly_recon_single AS AA, temperature_cru_mean AS BB WHERE AA.event_id IN(".$input_evid.") AND BB.month = ".$fmonth.";";

    //$query = "SELECT event_id, cru_diff_mean FROM temperatureStats WHERE event_id IN(".$input_evid.");";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $cruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $key = $line['event_id'];
        $val = round($line['cru_diff_mean'],2);
        if(empty($stats[$key])) $cruStats[$key] = array($val);
        else $cruStats[$key] = $val;
    }   

    // get temp stats from weather station data

    $input_evid = implode(",",$eventIds);
    $query = "SELECT event_id, id,  temp_recon - temperature as station_offset FROM temp_stations_relevant WHERE event_id IN(".$input_evid.");";

    $query="SELECT 
    AA.event_id, avg(ST_Value(AA.rast, BB.geom) - BB.temperature) as station_offset FROM (
    SELECT rast, event_id FROM temperature_monthly_recon_single WHERE event_id IN(".$input_evid.")
    ) as AA,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ". $fyear. " AND month = ".$fmonth."
    ) as BB
    WHERE ST_Intersects(AA.rast, BB.geom,1)
    GROUP BY event_id;";

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){  
        $key1 = $line['event_id'];
        $val = round($line['station_offset'],2);
        $stationStats[$key1]= $val;  
    }   


    $resres['idxPDat'] = $idxPDat;
    $resres['cruStats'] = $cruStats;
    $resres['stationStats'] = $stationStats;

    echo $_GET['callback'] . '(' . json_encode($resres) .')';


}elseif($fmode == 4){
    // get idx field data
    $resres = array();
    $idxPDat = array();
    $index = 0;
    $query = "SELECT recon_idxfield_temp_png, recon_interpol_temp_png_xmin, recon_interpol_temp_png_xmax, recon_interpol_temp_png_ymin, recon_interpol_temp_png_ymax FROM  regmod_idxfields_view WHERE year = ". $fyear. ' AND month = '.$fmonth.';';
    //$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){
        $idxPDatLine = array(); 
        foreach ($line as $key => $col_value) {
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $index++;
    }

    $resres['idxFieldDat'] = $idxPDat;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}elseif($fmode == 5){
    // get all years for select
    // get year and month as json 
    $query = 'SELECT Distinct year, month from regmod_views_test ORDER BY year, month ASC;'; 
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    $resres = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['year'];
        $val = $line['month'];
        if(empty($resres[$key])) $resres[$key] = array($val);
        else $resres[$key][] = $val;
    }   

    echo $_GET['callback'] . '(' . json_encode($resres) .')';


}elseif($fmode == 6){

    // get and mean all raster data for month as png 
    $query = 'SELECT Distinct year, month from regmod_views_test ORDER BY year, month ASC;'; 
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    $resres = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['year'];
        $val = $line['month'];
        if(empty($resres[$key])) $resres[$key] = array($val);
        else $resres[$key][] = $val;
    }   

    echo $_GET['callback'] . '(' . json_encode($resres) .')';


}elseif($fmode == 7){
    // get all index data points available 
    // get idx point data
    $resres = array();
    $idxPDat = array();
    $index = 0;

    $query = "SELECT * FROM tambora_temperature_monthly WHERE location != 'UNKNOWN - source';"; 

    // all data
    $query = "SELECT location, lon, lat,count(*) FROM tambora_temperature_monthly WHERE location != 'UNKNOWN - source'
    Group By location, lon, lat Order By count(*) ;";

    // all data on landsurface
    $query ="SELECT AA.location, AA.lon, AA.lat, count(*) FROM tambora_temperature_monthly AS AA INNER JOIN world_coastline_50m_poly as BB ON ST_Intersects(ST_TRANSFORM(ST_SetSRID(ST_MakePoint(AA.lon,AA.lat),4326),3857),BB.geom) WHERE location != 'UNKNOWN - source' and location_id != 7902
    Group By AA.location, AA.lon, AA.lat Order By count(*) ;";

    // only available data
    $query = "SELECT AA.location, AA.lon, AA.lat,count(*) FROM tambora_temperature_monthly as AA
    INNER JOIN temperature_monthly_regio_weight as BB ON AA.event_id = BB.event_id
    Group By AA.location, AA.lon, AA.lat Order By count(*) ;";


    /* all vali stations by count for relevant period
    $query="SELECT AA.lat, AA.lon, count(*)  FROM temperature_validation_stations AS AA INNER JOIN temperature_validation_data AS BB ON aa.station_id = BB.station_id Group by AA.lat, AA.lon Order by count(*);";
    */ 
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){

        $idxPDatLine = array(); 
        $eventId = array();
        foreach ($line as $key => $col_value) {
            if($key === "event_id"){
                $eventId = $col_value;
            }
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $eventIds[$index] = $eventId;
        $index++;
    }

    $resres['idxPDat'] = $idxPDat;
    //    $resres['stationStats'] = $stationStats;




    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}elseif($fmode == 8){

    $input_areaView = json_decode($input_areaView, true);

    // get all index data points available 
    // get idx point data
    $resres = array();
    $idxPDat = array();
    $index = 0;

    $query = "SELECT * FROM tambora_temperature_monthly WHERE location != 'UNKNOWN - source';"; 

    // all data
    $query = "SELECT location, lon, lat,count(*) FROM tambora_temperature_monthly WHERE location != 'UNKNOWN - source'
    Group By location, lon, lat Order By count(*) ;";

    // all data on landsurface
    $query ="SELECT AA.location, AA.lon, AA.lat, count(*) FROM tambora_temperature_monthly AS AA INNER JOIN world_coastline_50m_poly as BB ON ST_Intersects(ST_TRANSFORM(ST_SetSRID(ST_MakePoint(AA.lon,AA.lat),4326),3857),BB.geom) WHERE location != 'UNKNOWN - source' and location_id != 7902
    Group By AA.location, AA.lon, AA.lat Order By count(*) ;";

    // only available data
    $query = "SELECT AA.location, AA.lon, AA.lat,count(*) FROM tambora_temperature_monthly as AA WHERE ST_INTERSECTS(AA.geom,ST_MakeEnvelope(".$input_areaView[0]['lon'].",
    ".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",
    ".$input_areaView[1]['lat'].", 4326))
    Group By AA.location, AA.lon, AA.lat Order By count(*); ;";


    /* all vali stations by count for relevant period
    $query="SELECT AA.lat, AA.lon, count(*)  FROM temperature_validation_stations AS AA INNER JOIN temperature_validation_data AS BB ON aa.station_id = BB.station_id Group by AA.lat, AA.lon Order by count(*);";
    */ 
    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    while($line = pg_fetch_array($result)){

        $idxPDatLine = array(); 
        $eventId = array();
        foreach ($line as $key => $col_value) {
            if($key === "event_id"){
                $eventId = $col_value;
            }
            $idxPDatLine += array($key => $col_value);
        }
        $idxPDat[$index]  = $idxPDatLine; 
        $eventIds[$index] = $eventId;
        $index++;
    }

    $resres['idxPDat'] = $idxPDat;
    //    $resres['stationStats'] = $stationStats;




    echo $_GET['callback'] . '(' . json_encode($resres) .')';







}else{

}
/*
// Eine SQL-Abfrge ausführen
//echo "SELECT * FROM regmod_views_test WHERE recon_interpol_temp_png<>'' AND year_monthly_id= ". $fyear.$fmonth;
$query = "SELECT * FROM regmod_views_test WHERE recon_interpol_temp_png<>'' AND year_monthly_id= ". $fyear.$fmonth.';';
//$query = "SELECT year_monthly_id FROM regmod_views_test WHERE recon_interpol_temp_png<>'';";
$result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
$resres = array();
while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
foreach ($line as $key => $col_value) {
$resres += array($key => $col_value);
//echo '<br>';
//array_push($resres, $key => $col_value);
}
}
echo $_GET['callback'] . '(' . json_encode($resres) .')';
*/


// Speicher freigeben
pg_free_result($result);

// Verbindung schließen
pg_close($dbconn);

?>

