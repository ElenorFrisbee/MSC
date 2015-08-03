<?php
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

$input_mode = intval($_GET['mode']);
$input_century = intval($_GET['century']);
$input_decade = intval($_GET['decade']);
$input_year = intval($_GET['year']);
$input_areaView = $_GET['areaView'];

// establish connection
$dbconn = pg_connect('host=' . PGHOST . ' port=' . PGPORT . ' dbname=' . PGDATABASE . ' user=' . PGUSER . ' password=' . PGPASSWORD)  or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());


// get all years for select
// get year and month as json 
if($input_mode == 0 && !$input_areaView){
    $resres = array();
    foreach(range(10, 19, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = "SELECT
    decadeid, sum(event_count) as count
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
    temperature_monthly_recon AS data
    ON  data.year >= lookup.decadeID * 100
    AND data.year <  lookup.decadeID * 100 + 100  
    GROUP  BY decadeid
    Order By decadeid
    ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['decadeid'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   
    $selectData['century'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';


} else if($input_mode == 1 && intval($input_areaView) == 1){

    $minB = ($input_century)*10;
    $maxB = $minB +10;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = "
    SELECT
    decadeid, sum(event_count) as count
    FROM
    (
    SELECT
    trunc(year/10,0) as decadeID
    FROM
    temperature_monthly_recon
    GROUP BY
    trunc(year/10,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_recon AS data
    ON  data.year >= lookup.decadeID * 10
    AND data.year <  lookup.decadeID * 10 + 10  
    WHERE decadeid >= ".$minB." and decadeid < ".$maxB." GROUP  BY decadeid  Order By decadeid ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['decadeid'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   

    $selectData['decade'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

} else if($input_mode == 2 && intval($input_areaView) == 1){

    $minB = ($input_decade)*10;
    $maxB = $minB + 10;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = " -- COUNT BY year
    Select    year, sum(event_count) as count
    FROM      temperature_monthly_recon
    --WHERE     value_idx != 0
    WHERE year >= ".$minB." and year < ".$maxB."           
    GROUP BY  year
    Order By year;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['year'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   

    $selectData['year'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

} else if($input_mode == 3 && intval($input_areaView) == 1){

    $maxB = 12;
    $minB = 1;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = "   -- COUNT BY year
    Select    month, sum(event_count) as count
    FROM      temperature_monthly_recon
    --WHERE     value_idx != 0
    WHERE year = ".$input_year."
    GROUP BY  month
    Order By month
    ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['month'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }

    $selectData['month'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

} 


// SELECT TILES BASED ON AREA SELECTED
// get all years for select
// get year and month as json 
if($input_mode == 0 && $input_areaView && intval($input_areaView) != 1){
    $resres = array();
    foreach(range(10, 19, 1) as $number) {
        $resres[$number] = array(0);
    }

    $input_areaView = json_decode($input_areaView, true);

    $query = "SELECT
    decadeid, count(*)
    FROM
    (
    SELECT
    trunc(year/100,0) as decadeID
    FROM
    temperature_monthly_single_recon
    GROUP BY
    trunc(year/100,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_single_recon AS data
    ON  data.year >= lookup.decadeID * 100
    AND data.year <  lookup.decadeID * 100 + 100 

    INNER JOIN tambora_temperature_monthly as B ON data.event_id = B.event_id 
    WHERE ST_Intersects(B.location_geog, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))


    GROUP  BY decadeid
    Order By decadeid
    ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['decadeid'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   
    $selectData['century'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';


} else if($input_mode == 1 && intval($input_areaView) != 1){

    //echo json_decode($input_areaView);
    $input_areaView = json_decode($input_areaView, true);
    $minB = ($input_century)*10;
    $maxB = $minB +10;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = "
    SELECT
    decadeid, count(*)
    FROM
    (
    SELECT
    trunc(year/10,0) as decadeID
    FROM
    temperature_monthly_single_recon
    GROUP BY
    trunc(year/10,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_single_recon AS data
    ON  data.year >= lookup.decadeID * 10
    AND data.year <  lookup.decadeID * 10 + 10

    INNER JOIN tambora_temperature_monthly as B ON data.event_id = B.event_id 
    WHERE ST_Intersects(B.location_geog, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))

    AND decadeid >= ".$minB." and decadeid < ".$maxB." GROUP  BY decadeid  Order By decadeid ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['decadeid'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   

    $selectData['decade'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

} else if($input_mode == 2 && intval($input_areaView) != 1){

    $input_areaView = json_decode($input_areaView, true);
    $minB = ($input_decade)*10;
    $maxB = $minB + 10;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = " -- COUNT BY year
    Select    year, count(*)
    FROM      temperature_monthly_single_recon AS data
    --WHERE     value_idx != 0

    INNER JOIN tambora_temperature_monthly as B ON data.event_id = B.event_id 
    WHERE ST_Intersects(B.location_geog, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))

    AND year >= ".$minB." and year < ".$maxB."
    GROUP BY  year
    Order By year;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['year'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }   

    $selectData['year'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

} else if($input_mode == 3 && intval($input_areaView) != 1){

    $input_areaView = json_decode($input_areaView, true);
    $maxB = 12;
    $minB = 1;
    $resres = array();

    foreach(range($minB, $maxB, 1) as $number) {
        $resres[$number] = array(0);
    }

    $query = "   -- COUNT BY year
    Select    month, count(*)
    FROM      temperature_monthly_single_recon AS data
    --WHERE     value_idx != 0

    INNER JOIN tambora_temperature_monthly as B ON data.event_id = B.event_id 
    WHERE ST_Intersects(B.location_geog, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326))

    AND year = ".$input_year."
    GROUP BY  month
    Order By month
    ;"
    ;
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());

    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        $key = $line['month'];
        $val = $line['count'];
        $resres[$key] = array($val);
    }

    $selectData['month'] = $resres;
    echo $_GET['callback'] . '(' . json_encode($selectData) .')';

}else if($input_mode == 11){
    // returns json object for tile select with count for every century, decade, year and month
    // century => count
    //      decade => count
    //          year => count
    //              month => count

    $query="    SELECT
    centuryID*100 as century, decadeID*10 as decade, year, month, sum(event_count) as mtc
    FROM
    (
    SELECT
    trunc(year/100,0) as centuryID,
    trunc(year/10,0) as decadeID
    FROM
    temperature_monthly_recon
    GROUP BY
    trunc(year/100,0),
    trunc(year/10,0)
    )
    AS lookup
    INNER JOIN
    temperature_monthly_recon AS data
    ON  data.year >= lookup.centuryID * 100
    AND data.year <  lookup.centuryID * 100 + 100 
    AND data.year >= lookup.decadeID * 10
    AND data.year <  lookup.decadeID * 10 + 10  
    GROUP  BY centuryID,decadeID,year,month
    Order By centuryID,decadeID,year,month
    ;
    ";   

    $lineArray = array();
    $resArray = array();
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        // manage output data
        $century = $line['century'];

        $decade = $line['decade'];

        $year = $line['year'];

        $month = $line['month'];
        $mtc = $line['mtc'];

        if(empty($resArray[$century])){

            $lineArray = array(
                $century => array(
                    "count" => $mtc,
                    $decade => array(
                        "count" => $mtc,
                        $year => array(
                            "count" => $mtc,
                            $month => array(
                                "count" => $mtc 
                            )
                        )
                    )
                )
            );
            $resArray +=  $lineArray;

        } else if(empty($resArray[$century][$decade])){
            // update if decade not in array

            // update century count
            $resArray[$century]['count'] += $mtc;
            // add new decade entry
            $lineArray = array(
                $decade => array(
                    "count" => $mtc,
                    $year => array(
                        "count" => $mtc,
                        $month => array(
                            "count" => $mtc 
                        )
                    )
                )
            );

            $resArray[$century] +=  $lineArray;

        } else if(empty($resArray[$century][$decade][$year])){
            // update century count
            $resArray[$century]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade]['count'] += $mtc;

            // add new year entry
            $lineArray = array(
                $year => array(
                    "count" => $mtc,
                    $month => array(
                        "count" => $mtc 
                    )
                )
            );

            $resArray[$century][$decade] +=  $lineArray;

        } else if(empty($resArray[$century][$decade][$year][$month])){
            // update century count
            $resArray[$century]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade][$year]['count'] += $mtc;

            // add new month entry
            $lineArray = array(
                $month => array(
                    "count" => $mtc 
                )
            );

            $resArray[$century][$decade][$year] +=  $lineArray;
        }



    }
    echo $_GET['callback'] . '(' . json_encode($resArray) .')';

} else if($input_mode == 12){

    $input_areaView = json_decode($input_areaView, true);


    $query="SELECT
    centuryID*100 AS century, decadeID*10 AS decade, 
    lookup.year, lookup.month, count(*) AS mtc
    FROM (
    SELECT
        trunc(year/100,0) AS centuryID,
        trunc(year/10,0) AS decadeID,
        year, month
        FROM
            temperature_monthly_recon_single AS dataRast
            INNER JOIN
            tambora_temperature_monthly as dataPoint
                ON dataPoint.event_id = dataRast.event_id
                WHERE ST_INTERSECTS(dataPoint.geom, 
                    ST_MakeEnvelope(".$input_areaView[0]['lon'].",
                    ".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",
                    ".$input_areaView[1]['lat'].", 4326)) 
    ) AS lookup
    WHERE 
    lookup.year >= lookup.centuryID * 100
        AND lookup.year <  lookup.centuryID * 100 + 100 
        AND lookup.year >= lookup.decadeID * 10
        AND lookup.year <  lookup.decadeID * 10 + 10  
GROUP BY centuryID, decadeID, lookup.year, lookup.month
ORDER BY centuryID, decadeID, lookup.year, lookup.month;";

    $lineArray = array();
    $resArray = array();
    $result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
    while($line = pg_fetch_array($result, null, PGSQL_ASSOC)){

        // manage output data
        $century = $line['century'];

        $decade = $line['decade'];

        $year = $line['year'];

        $month = $line['month'];
        $mtc = $line['mtc'];

        if(empty($resArray[$century])){

            $lineArray = array(
                $century => array(
                    "count" => $mtc,
                    $decade => array(
                        "count" => $mtc,
                        $year => array(
                            "count" => $mtc,
                            $month => array(
                                "count" => $mtc 
                            )
                        )
                    )
                )
            );
            $resArray +=  $lineArray;

        } else if(empty($resArray[$century][$decade])){
            // update if decade not in array

            // update century count
            $resArray[$century]['count'] += $mtc;
            // add new decade entry
            $lineArray = array(
                $decade => array(
                    "count" => $mtc,
                    $year => array(
                        "count" => $mtc,
                        $month => array(
                            "count" => $mtc 
                        )
                    )
                )
            );

            $resArray[$century] +=  $lineArray;

        } else if(empty($resArray[$century][$decade][$year])){
            // update century count
            $resArray[$century]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade]['count'] += $mtc;

            // add new year entry
            $lineArray = array(
                $year => array(
                    "count" => $mtc,
                    $month => array(
                        "count" => $mtc 
                    )
                )
            );

            $resArray[$century][$decade] +=  $lineArray;

        } else if(empty($resArray[$century][$decade][$year][$month])){
            // update century count
            $resArray[$century]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade]['count'] += $mtc;
            // update decade count
            $resArray[$century][$decade][$year]['count'] += $mtc;

            // add new month entry
            $lineArray = array(
                $month => array(
                    "count" => $mtc 
                )
            );

            $resArray[$century][$decade][$year] +=  $lineArray;
        }



    }
    echo $_GET['callback'] . '(' . json_encode($resArray) .')';








}

?>