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
$input_areaCount = intval($_GET['areaCountExtent']);
$input_areaView = $_GET['areaView'];


// open color file
$colFileLoc = "col.txt";
$myfile = fopen($colFileLoc, "r") or die("Unable to open file!");

if($input_evid && $input_live == 1){ 

    /** The set bytea_output may be needed for PostgreSQL 9.0+, but not for 8.4 **/
    //  $blob = "'[rast1.val] + [rast2.val]'::text, '[rast2.val]'::text, '[rast1.val]'::text, NULL::double precision,'[rast1.val] + 1'::text, '1'::text, '[rast1.val]'::text, 0::double precision,'CASE WHEN [rast2.val] > 0 THEN [rast1.val] / [rast2.val]::float8 ELSE NULL END'::text, NULL::text, NULL::text, NULL::double precision";
    /*
    // only get intersected area of rasters (example here year 1740 mont 6 )
    $sql = "
    SELECT  
    ST_AsPNG(
    ST_ColorMap(
    (foo.rast),'".fread($myfile,filesize($colFileLoc))."'
    )
    )     
    FROM(
    SELECT ST_MapAlgebra(
    aaa.rast, 
    bbb.rast,
    '([rast2] + [rast1.val]) / 2'
    ) 
    AS rast FROM(
    SELECT rast FROM single_recon_temp_png WHERE year= 1740 and month=6 and event_id = 172343
    ) as aaa,(
    SELECT rast FROM single_recon_temp_png WHERE year= 1740 and month=6 and event_id = 173064
    ) as bbb
    WHERE ST_Intersects(aaa.rast, bbb.rast))as foo;
    ";

    // show whole month full cru map
    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(
    rast,'".fread($myfile,filesize($colFileLoc))."'
    )              
    ) 
    As bluered_png
    FROM cruMapsMean100 WHERE month = 8;
    ";  

    */

    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(
    ST_TRANSFORM(
    rast,3857),'".fread($myfile,filesize($colFileLoc))."'
    )             
    ) 
    FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array[". $input_evid."]));"
    ; 



    /*
    $sql= "SELECT 
    ST_AsPNG(
    ST_ColorMap(
    ST_TRANSFORM(
    ST_Union(f.rast, 'MAX'),3857)
    ,'".fread($myfile,filesize($colFileLoc))."')
    )
    FROM (SELECT rast
    FROM temperature_monthly_recon_live 
    WHERE year = ".$input_year." and month = ".$input_month." and event_id_hash = ".$input_evidHash."
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f";
    */


    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result); 

    // close color file
    fclose($myfile); 

    if ($row === false){

        exec("python /var/www/vhosts/default/htdocs/regmod/pcaPython/main.py ".$input_year." ".$input_month." ".str_replace(","," ", $input_evid), $eventHash);
        // open color file
        $myfile = fopen($colFileLoc, "r") or die("Unable to open file!");

        $sql = "
        SELECT 
        ST_AsPNG(
        ST_ColorMap(
        ST_TRANSFORM(
        rast,3857),'".fread($myfile,filesize($colFileLoc))."'
        )              
        ) 
        As bluered_png
        FROM temperature_monthly_recon_live WHERE year = ".$input_year." and month = ".$input_month." and event_id_hash = ".$input_evidHash.";"
        ;                                                                                                

        // execute query and fetch data 
        $result = pg_query($sql);
        $row = pg_fetch_row($result);
        pg_free_result($result);
        if ($row === false) return;

        // close color file
        fclose($myfile); 

    } 


    echo pg_unescape_bytea($row[0]);


} elseif($input_evid && $input_live == 2){


    // $sql = "SELECT DISTINCT idx_ymax, idx_xmax, idx_ymin, idx_xmin FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
    $sql = "SELECT (ST_MetaData(rast)).* FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array[". $input_evid."]));";          
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
elseif($input_year && $input_month && $input_areaView && intval($input_areaView) != 1 && !$input_lon && !$input_regiomax){
                                                                    
    $input_areaView = json_decode($input_areaView, true);

    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap( 
    ST_TRANSFORM(
    ST_Union(f.rast, 'MAX'),3857)
    ,'".fread($myfile,filesize($colFileLoc))."')
    )
    FROM (
    SELECT rast FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) IN(
    SELECT uniq(sort(array[event_id]))
    FROM
    tambora_temperature_monthly
    WHERE ST_Intersects(geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",
    ".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",
    ".$input_areaView[1]['lat'].", 4326)) AND year = ".$input_year." AND month = ".$input_month.")
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f;"
    ;          


    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);



} 
elseif($input_areaView && !$input_areaCount && !$input_year && !$input_month){      
                                                             
    //echo $input_areaView; 
    // xmin, double precision ymin, double precision xmax, double precision ymax,
    //  var imageBounds = [[bbox[1]['lat'], bbox[2]['lon']], [bbox[0]['lat'], bbox[0]['lon']]];  //  ymax, xmax, ymin, xmin                                                

    if(intval($input_areaView) == 1){

        // designed for working with trimed raster (which doasnt align) 
        // CAUTION Introduces some new Raster cells (billinear introduced the smalest amount of new data)!!!
        $sql ="
        SELECT 
        ST_AsPNG(
        ST_ColorMap(
        ST_SetBandNoDataValue(

        ST_UNION(ST_Transform(BB.rast,AA.rast,'Bilinear'),'COUNT'),0),'bluered'
        )
        )

        FROM temperature_monthly_single_recon as AA,
        temperature_monthly_recon as BB
        WHERE AA.rid = 53;";


        $sql = "
        SELECT 
        ST_AsPNG(
        ST_ColorMap(
        ST_SetBandNoDataValue(ST_Transform(ST_Union(rast,'COUNT'),3857),0),'bluered'
        )              
        ) 
        As bluered_png
        FROM temperature_monthly_recon;"
        ;       

    } else {
        $input_areaView = json_decode($input_areaView, true);
        /* get rectangle clipped union count from all data
        $sql = "
        SELECT ST_AsPNG(
        ST_ColorMap(ST_SetBandNoDataValue(ST_Clip(ST_Union(rast,'COUNT'),ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326)),0),'bluered'))  
        FROM temperature_monthly_recon
        WHERE ST_Intersects(rast, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326));"
        ;
        */
        $sql = "SELECT ST_AsPNG(
        ST_ColorMap(ST_SetBandNoDataValue(ST_Union(AA.rast,'COUNT'),0),'bluered')
        )  
        FROM temperature_monthly_recon_single AS AA INNER JOIN
        tambora_temperature_monthly AS BB ON AA.event_id = BB.event_id 
        WHERE ST_Intersects(BB.geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326));
        "
        ;

    }    



    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);


}
elseif($input_year && $input_month && !$input_extent && !$input_lon && !$input_regiomax) {
    $blob = "MEAN";
    /*  
    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(
    ST_UNION(rast,'".$blob."'),'".fread($myfile,filesize($colFileLoc))."'
    )              
    ) 
    As bluered_png
    FROM temperature_monthly_single_recon 
    WHERE year = ".$input_year." and month = ".$input_month.";"
    ;     

    */ 
    /*
    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(

    rast,'".fread($myfile,filesize($colFileLoc))."'
    )           
    ) 
    FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";"
    ;  
    */   

    $sql= "SELECT 
    ST_AsPNG(
    ST_ColorMap( 
    ST_TRANSFORM(
    ST_Union(f.rast, 'MAX'),3857)
    ,'".fread($myfile,filesize($colFileLoc))."')
    )
    FROM (SELECT rast
    FROM temperature_monthly_recon 
    WHERE year = ".$input_year." and month = ".$input_month."
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f";

    /* // clipped by germany
    $sql="   SELECT 
    ST_AsPNG(
    ST_ColorMap(

    ST_CLIP(AA.rast,BB.geom),'".fread($myfile,filesize($colFileLoc))."'
    )           
    ) 
    FROM temperature_monthly_recon AS AA, germany_poly AS BB WHERE AA.year = ".$input_year." and AA.month = ".$input_month.";"
    ;
    */

    /*

    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(
    ST_Transform(rast,3857),'".fread($myfile,filesize($colFileLoc))."'
    )              
    ) 
    As bluered_png
    FROM crumapsmean100 WHERE month = 5;"
    ;     


    */

    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);

} elseif(intval($input_extent) == 1){

    // $sql = "SELECT DISTINCT idx_ymax, idx_xmax, idx_ymin, idx_xmin FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
    $sql = "SELECT (ST_MetaData(rast)).* FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
    // clipped by germany$sql = "SELECT (ST_MetaData(ST_CLIP(AA.rast,BB.geom))).* FROM temperature_monthly_recon AS AA, germany_poly AS BB  WHERE AA.year = ".$input_year." and AA.month = ".$input_month.";";          
    //   $sql = "SELECT (ST_MetaData(rast)).* FROM crumapsmean100  WHERE month = 6;";          

    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $idxPDat = array();

    $line = pg_fetch_row($result);
    pg_free_result($result);
    if ($line === false){
        echo $_GET['callback'] .  '(' . json_encode('no data') .')';
        return;     
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

}elseif(intval($input_extent) == 2){

    // $sql = "SELECT DISTINCT idx_ymax, idx_xmax, idx_ymin, idx_xmin FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";";          
    $sql = "  SELECT 
    (ST_MetaData(ST_Union(rast,'COUNT'))).*
    FROM temperature_monthly_recon;";          

    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $idxPDat = array();

    $line = pg_fetch_row($result);
    pg_free_result($result);
    if ($line === false) return;

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

} elseif($input_year && $input_month && $input_lat && $input_lon && !$input_evid && intval($input_areaView) ==1 ){

    $sql="SELECT ST_Value(ST_TRANSFORM(rast,3857), ST_Transform(ST_SetSRID(ST_MakePoint(".$input_lon.", ".$input_lat."),4326),3857)) FROM temperature_monthly_recon WHERE year = ".$input_year." and month = ".$input_month.";
    ";

    // execute query and fetch data 
    $result = pg_query($sql);
    $line = pg_fetch_array($result);
    pg_free_result($result);
    if ($line === false) return;

    echo $_GET['callback'] . '(' . json_encode($line['st_value']) .')';



} elseif($input_year && $input_month && $input_lat && $input_lon && $input_evid && intval($input_areaView)==1  ){
    $input_areaView = json_decode($input_areaView, true);                                                                                                         
    $sql="SELECT ST_Value(ST_TRANSFORM(rast,3857), ST_Transform(ST_SetSRID(ST_MakePoint(".$input_lon.", ".$input_lat."),4326),3857)) FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array[". $input_evid."]));";

    // execute query and fetch data 
    $result = pg_query($sql);
    $line = pg_fetch_array($result);
    pg_free_result($result);
    if ($line === false) return;

    echo $_GET['callback'] . '(' . json_encode($line['st_value']) .')';



} elseif($input_year && $input_month && $input_lat && $input_lon && $input_evid){
    $sql="SELECT ST_Value(ST_TRANSFORM(rast,3857), ST_Transform(ST_SetSRID(ST_MakePoint(".$input_lon.", ".$input_lat."),4326),3857)) FROM temperature_monthly_recon_live WHERE year = ".$input_year." and month = ".$input_month." AND uniq(sort(event_id_array::int[])) = uniq(sort(array[". $input_evid."]));";

    // execute query and fetch data 
    $result = pg_query($sql);
    $line = pg_fetch_array($result);
    pg_free_result($result);
    if ($line === false) return;

    echo $_GET['callback'] . '(' . json_encode($line['st_value']) .')';


} elseif($input_cru == 1 && $input_month){

    $myfile = fopen($colFileLoc, "r") or die("Unable to open file!");

    $sql = "
    SELECT 
    ST_AsPNG(
    ST_ColorMap(
    ST_TRANSFORM(
    rast,3857),'".fread($myfile,filesize($colFileLoc))."'
    )              
    ) 

    FROM temperature_cru_mean WHERE month = ".$input_month.";"
    ;                                                                                                


    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);

}elseif($input_regiomax == 1 && !$input_year && !$input_month){

    $myfile = fopen($colFileLoc, "r") or die("Unable to open file!");
    $sql="
    SELECT   ST_AsPNG(
    ST_ColorMap(ST_TRANSFORM(ST_Union(f.rast, 'MAX'),3857),'bluered'))
    FROM (SELECT ST_UNION(rast,'MAX') as rast
    FROM temperature_monthly_regio_weight 
    WHERE event_id IN(".$input_evid.")
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f
    "  ;

    //$input_evids
    // --FROM temperature_monzhly_regio_weight WHERE event_id IN(".$input_evid.");"
    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);

}elseif($input_regiomax == 1 && $input_year && $input_month && intval($input_areaView)==1){
                                                    
    $sql="
    SELECT ST_AsPNG(
    ST_ColorMap(ST_TRANSFORM(ST_Union(f.rast, 'MAX'),3857),'bluered'))
    FROM (SELECT ST_UNION(rast,'MAX') as rast
    FROM temperature_monthly_regio_weight 
    WHERE event_id IN(SELECT event_id FROM tambora_temperature_monthly AS AA WHERE year = ".$input_year." AND month = ".$input_month."
    )
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f
    "  ;

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);

}elseif($input_regiomax == 1 && $input_year && $input_month && intval($input_areaView)!=1){
                     
    $input_areaView = json_decode($input_areaView, true);
      
    $sql="
    SELECT ST_AsPNG(
    ST_ColorMap(ST_TRANSFORM(ST_Union(f.rast, 'MAX'),3857),'bluered'))
    FROM (SELECT ST_UNION(rast,'MAX') as rast
    FROM temperature_monthly_regio_weight 
    WHERE event_id IN(SELECT event_id FROM tambora_temperature_monthly AS AA WHERE year = ".$input_year." AND month = ".$input_month." AND ST_INTERSECTS(geom,ST_MakeEnvelope(".$input_areaView[0]['lon'].",
    ".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",
    ".$input_areaView[1]['lat'].", 4326))
    )
    UNION ALL 
    SELECT ST_MAKEEMPTYRASTER(rast) as rast
    FROM temperature_cru_mean
    WHERE month=1) As f;
    "  ;

    // execute query and fetch data 
    $result = pg_query($sql);
    $row = pg_fetch_row($result);
    pg_free_result($result);
    if ($row === false) return;
    echo pg_unescape_bytea($row[0]);

}elseif($input_areaCount == 1){

    $input_areaView = json_decode($input_areaView, true);

    $sql = "SELECT (ST_MetaData(ST_Union(AA.rast,'COUNT'))).*
    FROM temperature_monthly_recon_single AS AA INNER JOIN
    tambora_temperature_monthly AS BB ON AA.event_id = BB.event_id 
    WHERE ST_Intersects(BB.geom, ST_MakeEnvelope(".$input_areaView[0]['lon'].",".$input_areaView[0]['lat'].",".$input_areaView[2]['lon'].",".$input_areaView[1]['lat'].", 4326));
    "
    ;


    fclose($myfile);

    // execute query and fetch data 
    $result = pg_query($sql);
    $idxPDat = array();

    $line = pg_fetch_row($result);
    pg_free_result($result);
    if ($line === false) return;

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