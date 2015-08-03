<?php

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


function my_std($aValues, $bSample = false)
{
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
    foreach ($aValues as $i)
    {
        $fVariance += pow($i - $fMean, 2);
    }
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    return (float) sqrt($fVariance);
}

/*
* Merge connect string and connect db server with default parameters.
*/

$fyear = $_GET['year'];
$fmonth = $_GET['month'];
$fmode = $_GET['mode'];
$fevid = $_GET['evid'];
$input_evidHash = intval($_GET['evidHash']);
$input_areaView = $_GET['areaView'];



$dbconn = pg_connect('host=' . PGHOST . ' port=' . PGPORT . ' dbname=' . PGDATABASE . ' user=' . PGUSER . ' password=' . PGPASSWORD)  or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());

header('Content-Type: application/json');


if($fmode == 0 && intval($input_areaView) == 1){

    $idxPDat = array();
    $resres = array();
    $index = 0;

    // see available vali_stations for selected year/month/event_id 
    /*
    $query = "SELECT  
    bbb.lat, bbb.lon, bbb.name FROM(
    SELECT rast FROM monthly_recon_temp WHERE year= 1740 and month=1
    ) as aaa,(
    SELECT lat, lon, name, geom FROM vali_station INNER JOIN vali_temperature ON vali_station.id=vali_temperature.station_id WHERE vali_temperature.year = 1740 and vali_temperature.month=1
    ) as bbb
    WHERE ST_Intersects(aaa.rast, bbb.geom);";
    */

    // notice to get only desired stats field you have tu end the selector with . and than the field name, or .* for every field in a new column
    // notice round and cast of value, this is because of distinct selection, else multiple same events were listed            
    $query ="SELECT DISTINCT  
    round(CAST(float8 ((ST_SummaryStats(aaa.rast)).stddev) as numeric), 2) as stats, ST_Value(aaa.rast, bbb.geom) as temp_recon, bbb.temperature, bbb.station_id, bbb.lat, bbb.lon, bbb.name, bbb.elevation, bbb.rural
    FROM(
    SELECT rast FROM temperature_monthly_recon WHERE year= ".$fyear." and month = ".$fmonth."
    ) as aaa,(
    SELECT lat, lon, name, geom as geom, temperature, temperature_validation_stations.station_id, elevation, rural FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE temperature_validation_data.year = ".$fyear." and temperature_validation_data.month=".$fmonth."
    ) as bbb
    WHERE ST_Intersects(aaa.rast,1, bbb.geom);
    ";   


    /* display all validation stations for relevant timeframe
    $query="SELECT DISTINCT AA.lat, AA.lon, AA.name  FROM temperature_validation_stations AS AA INNER JOIN temperature_validation_data AS BB ON aa.station_id = BB.station_id;";
    */

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
        $idxPDatLine = array(); 
        $idxPDatDiff = array();

        foreach ($line as $key => $col_value) {
            $idxPDatLine += array($key => $col_value);
        }

        $diff = $idxPDatLine['temp_recon'] - $idxPDatLine['temperature'];

        // calculate multiple offset off stdev for coloring
        if(abs($diff) < $line['stats']){
            $idxPDatLine += array('stdOff' => 0);   
        }elseif(abs($diff) > $line['stats'] && abs($diff) < $line['stats']*2){
            $idxPDatLine += array('stdOff' =>1);    
        }elseif(abs($diff) > $line['stats']*2 && abs($diff) < $line['stats']*3){
            $idxPDatLine += array('stdOff' => 2);  
        }else{
            $idxPDatLine += array('stdOff' => 3);   
        }

        array_push($idxPDatDiff, $diff); 
        array_push($idxPDat, $idxPDatLine); 
    }   

    // calc mean offset of reconstructed temp to station data
    $idxPDatMean = array_sum($idxPDatDiff) / count($idxPDatDiff);     

    $resres['idxPDat'] = $idxPDat;
    $resres['idxPDatMean'] = $idxPDatMean;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}elseif($fmode == 0 && intval($input_areaView) != 1){

    $idxPDat = array();
    $resres = array();
    $index = 0;
    $input_areaView = json_decode($input_areaView, true);

    // see available vali_stations for selected year/month/event_id 
    /*
    $query = "SELECT  
    bbb.lat, bbb.lon, bbb.name FROM(
    SELECT rast FROM monthly_recon_temp WHERE year= 1740 and month=1
    ) as aaa,(
    SELECT lat, lon, name, geom FROM vali_station INNER JOIN vali_temperature ON vali_station.id=vali_temperature.station_id WHERE vali_temperature.year = 1740 and vali_temperature.month=1
    ) as bbb
    WHERE ST_Intersects(aaa.rast, bbb.geom);";
    */

    // notice to get only desired stats field you have tu end the selector with . and than the field name, or .* for every field in a new column
    // notice round and cast of value, this is because of distinct selection, else multiple same events were listed            
    $query ="SELECT DISTINCT  
    round(CAST(float8 ((ST_SummaryStats(aaa.rast)).stddev) as numeric), 2) as stats, ST_Value(aaa.rast, bbb.geom) as temp_recon, bbb.temperature, bbb.station_id, bbb.lat, bbb.lon, bbb.name, bbb.elevation, bbb.rural
    FROM(
    SELECT rast FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) IN(
    SELECT uniq(sort(array[event_id]))
    FROM
    tambora_temperature_monthly
    WHERE ST_Intersects(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326)) AND year = ".$fyear." AND month = ".$fmonth.")
    ) as aaa,(
    SELECT lat, lon, name, geom as geom, temperature, temperature_validation_stations.station_id, elevation, rural FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE temperature_validation_data.year = ".$fyear." and temperature_validation_data.month=".$fmonth."
    ) as bbb
    WHERE ST_Intersects(aaa.rast,1, bbb.geom);
    ";   

    /* display all validation stations for relevant timeframe
    $query="SELECT DISTINCT AA.lat, AA.lon, AA.name  FROM temperature_validation_stations AS AA INNER JOIN temperature_validation_data AS BB ON aa.station_id = BB.station_id;";
    */

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
        $idxPDatLine = array(); 
        $idxPDatDiff = array();

        foreach ($line as $key => $col_value) {
            $idxPDatLine += array($key => $col_value);
        }

        $diff = $idxPDatLine['temp_recon'] - $idxPDatLine['temperature'];

        // calculate multiple offset off stdev for coloring
        if(abs($diff) < $line['stats']){
            $idxPDatLine += array('stdOff' => 0);   
        }elseif(abs($diff) > $line['stats'] && abs($diff) < $line['stats']*2){
            $idxPDatLine += array('stdOff' =>1);    
        }elseif(abs($diff) > $line['stats']*2 && abs($diff) < $line['stats']*3){
            $idxPDatLine += array('stdOff' => 2);  
        }else{
            $idxPDatLine += array('stdOff' => 3);   
        }

        array_push($idxPDatDiff, $diff); 
        array_push($idxPDat, $idxPDatLine); 
    }   

    // calc mean offset of reconstructed temp to station data
    $idxPDatMean = array_sum($idxPDatDiff) / count($idxPDatDiff);     

    $resres['idxPDat'] = $idxPDat;
    $resres['idxPDatMean'] = $idxPDatMean;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

} elseif($fmode == 1 && intval($input_areaView) == 1){

    $idxPDat = array();
    $index = 0;

    //cru statistics by many layer for aaa Intersection
    $query ="SELECT  
    ST_SummaryStats(ST_Intersection(aaa.rast, bbb.rast,1)) as cru, ST_SummaryStats(bbb.rast) as regmod FROM(
    SELECT rast FROM temperature_cru_mean WHERE month= ".$fmonth."
    ) as aaa,(
    SELECT rast FROM temperature_monthly_recon_live WHERE year = ".$fyear." and month = ".$fmonth." AND uniq(sort(event_id_array::int[])) = uniq(sort(array[".$fevid."]))
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    $CruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $cru = str_replace(array( '(', ')' ), '', $line['cru']);
        $regmod = str_replace(array( '(', ')' ), '', $line['regmod']);
        $cru = explode(",", $cru);
        $regmod = explode(",", $regmod);
        $CruStats['cru']['mean'] = round($cru[2],2);
        $CruStats['cru']['stddev'] = round($cru[3],2);
        $CruStats['cru']['min'] = round($cru[4],2);
        $CruStats['cru']['max'] = round($cru[5],2);
        $CruStats['regmod']['mean'] = round($regmod[2],2);
        $CruStats['regmod']['stddev'] = round($regmod[3],2);
        $CruStats['regmod']['min'] = round($regmod[4],2);
        $CruStats['regmod']['max'] = round($regmod[5],2);
    }   

    $resres['cruStats'] = $CruStats;

    // get Station temperature and regmod temperature vor station lat/lon point
    $query ="SELECT avg(ST_Value(aaa.rast, bbb.geom) - bbb.temperature) AS station_offset  FROM (
    SELECT rast FROM temperature_monthly_recon_live  WHERE year = ".$fyear." and month = ".$fmonth." AND uniq(sort(event_id_array::int[])) = uniq(sort(array[".$fevid."]))
    ) as aaa,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ".$fyear." and month = ".$fmonth."
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if($row === false){
        $stationStats['mean'] = "null";  
    } else {
        $stationStats['mean'] = round($row[0],2);    
    }

    $resres['stationStats'] = $stationStats;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}elseif($fmode == 1 && intval($input_areaView) != 1){

    $idxPDat = array();
    $index = 0;
    $input_areaView = json_decode($input_areaView, true);

    //cru statistics by many layer for aaa Intersection
    $query ="SELECT  
    ST_SummaryStats(ST_Intersection(aaa.rast, bbb.rast,1)) as cru, ST_SummaryStats(bbb.rast) as regmod FROM(
    SELECT rast FROM temperature_cru_mean WHERE month= ".$fyear."
    ) as aaa,(
    SELECT rast FROM temperature_monthly_recon_live WHERE year = ".$fyear." and month = ".$fmonth." AND uniq(sort(event_id_array::int[])) = uniq(sort(array[".$fevid."])) AND uniq(sort(event_id_array::int[])) IN (SELECT uniq(sort(event_id_array::int[])) FROM tambora_temperature_monthly WHERE ST_INTERSECTS(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326)))
    ) as bbb
    ;
    "
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    $CruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $cru = str_replace(array( '(', ')' ), '', $line['cru']);
        $regmod = str_replace(array( '(', ')' ), '', $line['regmod']);
        $cru = explode(",", $cru);
        $regmod = explode(",", $regmod);
        $CruStats['cru']['mean'] = round($cru[2],2);
        $CruStats['cru']['stddev'] = round($cru[3],2);
        $CruStats['cru']['min'] = round($cru[4],2);
        $CruStats['cru']['max'] = round($cru[5],2);
        $CruStats['regmod']['mean'] = round($regmod[2],2);
        $CruStats['regmod']['stddev'] = round($regmod[3],2);
        $CruStats['regmod']['min'] = round($regmod[4],2);
        $CruStats['regmod']['max'] = round($regmod[5],2);
    }   

    $resres['cruStats'] = $CruStats;

    // get Station temperature and regmod temperature vor station lat/lon point
    $query ="SELECT avg(ST_Value(aaa.rast, bbb.geom) - bbb.temperature) AS station_offset  FROM (
    SELECT rast FROM temperature_monthly_recon_live  WHERE year = ".$fyear." and month = ".$fmonth." AND uniq(sort(event_id_array::int[])) = uniq(sort(array[".$fevid."]))
    ) as aaa,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ".$fyear." and month = ".$fmonth."
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if($row === false){
        $stationStats['mean'] = "null";  
    } else {
        $stationStats['mean'] = round($row[0],2);    
    }

    $resres['stationStats'] = $stationStats;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

} elseif($fmode == 2  && intval($input_areaView) == 1) {

    $idxPDat = array();
    $index = 0;

    //cru statistics by many layer for aaa Intersection
    $query ="SELECT  
    ST_SummaryStats(ST_Intersection(aaa.rast, bbb.rast,1)) as cru, ST_SummaryStats(bbb.rast) as regmod FROM(
    SELECT rast FROM temperature_cru_mean WHERE month= ".$fmonth."
    ) as aaa,(
    SELECT rast FROM temperature_monthly_recon WHERE year = ".$fyear." and month = ".$fmonth."
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    $CruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $cru = str_replace(array( '(', ')' ), '', $line['cru']);
        $regmod = str_replace(array( '(', ')' ), '', $line['regmod']);
        $cru = explode(",", $cru);
        $regmod = explode(",", $regmod);
        $CruStats['cru']['mean'] = round($cru[2],2);
        $CruStats['cru']['stddev'] = round($cru[3],2);
        $CruStats['cru']['min'] = round($cru[4],2);
        $CruStats['cru']['max'] = round($cru[5],2);
        $CruStats['regmod']['mean'] = round($regmod[2],2);
        $CruStats['regmod']['stddev'] = round($regmod[3],2);
        $CruStats['regmod']['min'] = round($regmod[4],2);
        $CruStats['regmod']['max'] = round($regmod[5],2);
    }   

    $resres['cruStats'] = $CruStats;

    // get Station temperature and regmod temperature vor station lat/lon point
    $query ="SELECT 
    ST_Intersects(aaa.rast, bbb.geom,1), ST_Value(aaa.rast, bbb.geom) as temp_recon, bbb.temperature, bbb.lat, bbb.lon, bbb.name FROM (
    SELECT rast FROM temperature_monthly_recon  WHERE year = ".$fyear." and month = ".$fmonth." 
    ) as aaa,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ".$fyear." and month = ".$fmonth."
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();
    $stack = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        if($line['st_intersects'] == 't'){    
            $diff = $line['temp_recon'] - $line['temperature'];             
            array_push($stack, $diff);
            $sum =  $sum + $diff;
        }
    }    

    // calculate mean an and std of intersecting data
    $count = count($stack);
    if($count>0){
        $stationStats['mean'] = $sum/$count;
        $stationStats['std'] = my_std($stack);
    } else{
        $stationStats['mean'] = $diff;
        $stationStats['std'] = 16;
    }


    if($stationStats['mean']== "" || $stationStats['std']== ""){
        $stationStats['mean'] = "null";    
        $stationStats['std'] = "null";    
    }
    $resres['stationStats'] = $stationStats;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

} elseif($fmode == 2 && intval($input_areaView) != 1) {
                    
    $idxPDat = array();
    $index = 0;
    $input_areaView = json_decode($input_areaView, true);

    //cru statistics by many layer for aaa Intersection
    $query =" SELECT  
    ST_SummaryStats(ST_Intersection(aaa.rast, bbb.rast,1)) as cru, ST_SummaryStats(bbb.rast) as regmod FROM(
    SELECT rast FROM temperature_cru_mean WHERE month= ".$fmonth."
    ) as aaa,(
    SELECT rast FROM temperature_monthly_recon_live WHERE year = ".$fyear." AND month= ".$fmonth." AND uniq(sort(event_id_array::int[])) IN (SELECT uniq(sort(array[event_id])) FROM tambora_temperature_monthly WHERE ST_INTERSECTS(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326)))
    ) as bbb
    ;"
    ;
   

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    $CruStats = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        $cru = str_replace(array( '(', ')' ), '', $line['cru']);
        $regmod = str_replace(array( '(', ')' ), '', $line['regmod']);
        $cru = explode(",", $cru);
        $regmod = explode(",", $regmod);
        $CruStats['cru']['mean'] = round($cru[2],2);
        $CruStats['cru']['stddev'] = round($cru[3],2);
        $CruStats['cru']['min'] = round($cru[4],2);
        $CruStats['cru']['max'] = round($cru[5],2);
        $CruStats['regmod']['mean'] = round($regmod[2],2);
        $CruStats['regmod']['stddev'] = round($regmod[3],2);
        $CruStats['regmod']['min'] = round($regmod[4],2);
        $CruStats['regmod']['max'] = round($regmod[5],2);
    }   

    $resres['cruStats'] = $CruStats;

    // get Station temperature and regmod temperature vor station lat/lon point
    $query ="SELECT 
    ST_Intersects(aaa.rast, bbb.geom,1), ST_Value(aaa.rast, bbb.geom) as temp_recon, bbb.temperature, bbb.lat, bbb.lon, bbb.name FROM (
    SELECT rast FROM temperature_monthly_recon_live  WHERE year = ".$fyear." and month = ".$fmonth." AND uniq(sort(event_id_array::int[])) IN (SELECT uniq(sort(array[event_id])) FROM tambora_temperature_monthly WHERE ST_INTERSECTS(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))) 
    ) as aaa,(
    SELECT * FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE year = ".$fyear." and month = ".$fmonth."
    ) as bbb
    ;"
    ;

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());

    $stationStats = array();
    $stack = array();
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){
        if($line['st_intersects'] == 't'){    
            $diff = $line['temp_recon'] - $line['temperature'];             
            array_push($stack, $diff);
            $sum =  $sum + $diff;
        }
    }    

    // calculate mean an and std of intersecting data
    $count = count($stack);
    if($count>0){
        $stationStats['mean'] = $sum/$count;
        $stationStats['std'] = my_std($stack);
    } else{
        $stationStats['mean'] = $diff;
        $stationStats['std'] = 16;
    }


    if($stationStats['mean']== "" || $stationStats['std']== ""){
        $stationStats['mean'] = "null";    
        $stationStats['std'] = "null";    
    }
    $resres['stationStats'] = $stationStats;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

} elseif($fmode == 3){

    $idxPDat = array();
    $resres = array();
    $index = 0;
    $input_areaView = json_decode($input_areaView, true);


    // see available vali_stations for selected year/month/event_id 
    /*
    $query = "SELECT  
    bbb.lat, bbb.lon, bbb.name FROM(
    SELECT rast FROM monthly_recon_temp WHERE year= 1740 and month=1
    ) as aaa,(
    SELECT lat, lon, name, geom FROM vali_station INNER JOIN vali_temperature ON vali_station.id=vali_temperature.station_id WHERE vali_temperature.year = 1740 and vali_temperature.month=1
    ) as bbb
    WHERE ST_Intersects(aaa.rast, bbb.geom);";
    */

    // notice to get only desired stats field you have tu end the selector with . and than the field name, or .* for every field in a new column
    // notice round and cast of value, this is because of distinct selection, else multiple same events were listed            
    $query ="  SELECT DISTINCT  
    round(CAST(float8 ((ST_SummaryStats(aaa.rast)).stddev) as numeric), 2) as stats, ST_Value(aaa.rast, bbb.geom) as temp_recon, bbb.temperature, bbb.station_id, bbb.lat, bbb.lon, bbb.name, bbb.elevation, bbb.rural
    FROM(
    SELECT rast FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array[".$fevid."]))
    ) as aaa,(
    SELECT lat, lon, name, geom as geom, temperature, temperature_validation_stations.station_id, elevation, rural FROM temperature_validation_stations INNER JOIN temperature_validation_data ON temperature_validation_stations.station_id=temperature_validation_data.station_id WHERE temperature_validation_data.year = ".$fyear." and temperature_validation_data.month=".$fmonth."
    ) as bbb
    WHERE ST_Intersects(aaa.rast,1, bbb.geom); ";   

    /* display all validation stations for relevant timeframe
    $query="SELECT DISTINCT AA.lat, AA.lon, AA.name  FROM temperature_validation_stations AS AA INNER JOIN temperature_validation_data AS BB ON aa.station_id = BB.station_id;";
    */

    $result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
    while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
        $idxPDatLine = array(); 
        $idxPDatDiff = array();

        foreach ($line as $key => $col_value) {
            $idxPDatLine += array($key => $col_value);
        }

        $diff = $idxPDatLine['temp_recon'] - $idxPDatLine['temperature'];

        // calculate multiple offset off stdev for coloring
        if(abs($diff) < $line['stats']){
            $idxPDatLine += array('stdOff' => 0);   
        }elseif(abs($diff) > $line['stats'] && abs($diff) < $line['stats']*2){
            $idxPDatLine += array('stdOff' =>1);    
        }elseif(abs($diff) > $line['stats']*2 && abs($diff) < $line['stats']*3){
            $idxPDatLine += array('stdOff' => 2);  
        }else{
            $idxPDatLine += array('stdOff' => 3);   
        }

        array_push($idxPDatDiff, $diff); 
        array_push($idxPDat, $idxPDatLine); 
    }   

    // calc mean offset of reconstructed temp to station data
    $idxPDatMean = array_sum($idxPDatDiff) / count($idxPDatDiff);     

    $resres['idxPDat'] = $idxPDat;
    $resres['idxPDatMean'] = $idxPDatMean;
    echo $_GET['callback'] . '(' . json_encode($resres) .')';

}  
?>                    


