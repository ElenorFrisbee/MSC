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


$input_areaView = $_GET['areaView'];

/*
* Merge connect string and connect db server with default parameters.
*/
$dbconn = pg_connect('host=' . PGHOST . ' port=' . PGPORT . ' dbname=' . PGDATABASE . ' user=' . PGUSER . ' password=' . PGPASSWORD)  or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());

header('Content-Type: application/json');

if($input_areaView){

    $input_areaView = json_decode($input_areaView, true);


    $query = " SELECT
    lookup.year, lookup.month, lookup.event_id, (ST_SummaryStats(KK.rast)).mean
    FROM
    (
    SELECT
    year,
    month,
   event_id
    FROM
    tambora_temperature_monthly
    WHERE ST_Intersects(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))
    )
    AS lookup
    INNER JOIN
    temperature_monthly_recon_single AS KK
    ON  lookup.event_id = KK.event_id
    Order By lookup.year,lookup.month";


} else {

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

    $query ="SELECT
    year, month, (ST_SummaryStats(rast)).mean
    FROM
    (
    SELECT
    trunc(year/100,0) as decadeID
    FROM
    temperature_monthly_recon
    GROUP BY
    trunc(year/100,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_recon
    AS data
    ON  data.year >= lookup.decadeID * 100
    AND data.year <  lookup.decadeID * 100 + 100 
    GROUP  BY year, month, rast
    Order By year, month
    ;"
    ;        
}

$timeline = array();
$result = pg_query($query) or die('Cannot execute query: ' . pg_last_error()); 
while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
    $timeline[$line['year']][$line['month']]['mean'] = round($line['mean'],2);
} 

$restimeline = array();
$resline = array();
// get mean temperature value for month       
foreach($timeline as $year => $value){
    foreach($timeline[$year] as $month => $value){
        $resline['date'] = $year.'-'.sprintf('%02d', $month).'-01';
        $resline['mean'] =  $timeline[$year][$month]['mean'];
        array_push($restimeline,$resline);
    }
}                    

echo $_GET['callback'] . '(' . json_encode($restimeline) .')';


// returns all reconstructed mean data and for no data cru mean of month 
/*


    // see available vali_stations for selected year/month/event_id 


    $query ="SELECT DISTINCT
    year, month, (ST_SummaryStats(rast)).mean as mean
    FROM
    (
    SELECT
    trunc(year/100,0) as decadeID
    FROM
    temperature_monthly_recon
    GROUP BY
    trunc(year/100,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_recon
    AS data
    ON  data.year >= lookup.decadeID * 100
    AND data.year <  lookup.decadeID * 100 + 100 
    GROUP  BY year, month, rast
    Order By year, month
    ;"
    ;        
}

// get available reconstructed mean data
$reconData = array();
$result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
    $reconData[$line['year']][$line['month']]['mean'] = round($line['mean'],2);
    $maxYear = $line['year'];
} 
$minYear = key($reconData);


//echo $_GET['callback'] . '(' . json_encode($timeline) .')'; 

// get cru mean data 
$query ="SELECT month, (ST_SUMMARYSTATS(rast)).mean FROM temperature_cru_mean ORDER BY month;";        
$cruData = array();
$result = pg_query($query) or die('Cannot execute query: ' . pg_last_error());
while($line = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
    $cruData[$line['month']]['mean'] = round($line['mean'],2);
}

// create arry for all month in century
$centuryArr = array();
$year = 0;
while($minYear <= $maxYear) {
    $month=1;
    while($month <= 12){
        if($reconData[$minYear][$month]['mean']){
            $value = $reconData[$minYear][$month]['mean'];     
        } else {
            $value = $cruData[$month]['mean'];
        }
        $centuryArr[$minYear][$month]['mean'] = $value;
        ++$month;
    }
    ++$minYear;  
} 

//echo $_GET['callback'] . '(' . json_encode($centuryArr) .')'; 
//echo $_GET['callback'] . '(' . json_encode( key($reconData)) .')'; 




$restimeline = array();
$resline = array();
// get mean temperature value for month       
foreach($centuryArr as $year => $value){
    foreach($centuryArr[$year] as $month => $value){
        $count = count($centuryArr[$year][$month]['mean']);
            $resline['date'] = $year.'-'.sprintf('%02d', $month).'-01';
            $resline['mean'] =  $centuryArr[$year][$month]['mean'];
            array_push($restimeline,$resline);
    }
}                    

echo $_GET['callback'] . '(' .
*/
?>                    


