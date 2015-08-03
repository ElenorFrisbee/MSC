<?php

$conn_str ='dbname host port user password';
$dbconn = pg_connect($conn_str);
//header('Content-Type: image/png');  

$input_year = intval($_GET['year']);
$input_month = intval($_GET['month']);
$input_evid = $_GET['evid'];
$input_evidHash = intval($_GET['evidHash']);
$input_areaView = $_GET['areaView'];
$input_extent = $_GET['extent'];
$getLocTemp = $_GET['locTemp'];
$input_lon = $_GET['lon'];
$input_lat = $_GET['lat'];
$input_live = intval($_GET['live']);
$input_cru = intval($_GET['crumean']);
$input_regiomax = intval($_GET['regiomax']);

// open color file
$colFileLoc = "col.txt";
$myfile = fopen($colFileLoc, "r") or die("Unable to open file!");

if($input_evid && $input_live == 2){


    // $sql = "SELECT DISTINCT idx_ymax, idx_xmax, idx_ymin, idx_xmin FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
    $sql = "SELECT (ST_MetaData(rast)).* FROM temperature_monthly_recon_live WHERE year = ".$input_year." and month = ".$input_month." and uniq(sort(event_id_array::int[])) = uniq(sort(array[". $input_evid."]));";          
    //   $sql = "SELECT (ST_MetaData(rast)).* FROM crumapsmean100  WHERE month = 6;";          


    // execute query and fetch data 
    $result = pg_query($sql);
    $idxPDat = array();

    $line = pg_fetch_row($result);
    pg_free_result($result);

    if ($line === false){

        exec("python /var/www/vhosts/default/htdocs/regmod/pcaPython/main.py ".$input_year." ".$input_month." ".str_replace(","," ", $input_evid), $eventHash);

        // $sql = "SELECT DISTINCT idx_ymax, idx_xmax, idx_ymin, idx_xmin FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
        $sql = "SELECT (ST_MetaData(rast)).* FROM temperature_monthly_recon_live WHERE year = ".$input_year." and month = ".$input_month." and event_id_hash = ".$input_evidHash.";";        
        //   $sql = "SELECT (ST_MetaData(rast)).* FROM crumapsmean100  WHERE month = 6;";          


        // execute query and fetch data 
        $result = pg_query($sql);
        $idxPDat = array();

        $line = pg_fetch_row($result);
        pg_free_result($result);
        if ($line === false) return;

    }

    $xmin = $line[0];
    $ymax = $line[1];
    $xmax = $xmin + ($line[2] * $line[4]); 
    $ymin = $ymax + ($line[3] * $line[5]);

    $idxPDat = array('ymax' => floatval($ymax),
        'xmax' => $xmax,
        'ymin' => $ymin,
        'xmin' => floatval($xmin));

    $resres['idxPDat'] = $idxPDat;

    echo $_GET['callback'] . '(' . json_encode($resres) .')';
} 

// Speicher freigeben
pg_free_result($result);
// Verbindung schließen
pg_close($dbconn);

?>                    