// Populate Info Table and Text
function getStats(res){
    // Stats for station data
    if(typeof res['stationStats'] !== 'undefined'){
        var allStOff = [];
        for(var evid in res['stationStats']){
            for(vals in res['stationStats'][evid]){
                allStOff.push(res['stationStats'][evid][vals]);   
            }    
        }
        var avgSt = average(allStOff).toFixed(2);
        var stdSt = stddev(allStOff).toFixed(2);               

        // set stats text
        if(avgSt != "" && stddev != "") {
            $('#stationTextMean').text(avgSt);
            $('#stationTextStd').text(stdSt);
        } else {
            $('#stationTextMean').text(null);    
            $('#stationTextStd').text(null);    
        }
    }

    // cru Stats
    if(typeof res['cruStats'] !== 'undefined'){
        var allCruOff = [];
        for(var evid in res['cruStats']){
            allCruOff.push(res['cruStats'][evid][0]);   
        }
        var avgCru = average(allCruOff).toFixed(2);

        // set stats text
        if(avgCru != "") {
            $('#cruText').text(avgCru);
        } else {
            $('#cruText').text(null);    
        }
    }

    // show stats
    // TODO: FIX THIS THAT STAT WERE ONLY DISPALAYED IF AVAILABLE 
    if(avgSt != "" || stddev != "" || avgCru != ""){
    } else {
    }
}

function updateStats(selectedEventIds, year, month){
    if(selectedEventIds[0] != null && selectedEventIds.length != allEventIds.length){
        var sum = selectedEventIds.reduce(function(a, b) { return parseInt(a) + parseInt(b); }); 
        var evHash = sum/selectedEventIds.length 
        var bbox = checkBoundingBox()[0];
        $.getJSON(window.location+'valiPoints.php?callback=?&mode=1&year='+year+'&month='+month+'&evid='+selectedEventIds.toString()+'&areaView='+bbox,
            function(res){ 
                console.log('vali1')
                // check if view is available in db
                var cruOff = (res['cruStats']['regmod']['mean'] - res['cruStats']['cru']['mean']).toFixed(2)
                if(res['stationStats']['mean'] != "null") {
                    var stationOffMean = (res['stationStats']['mean']);
                    $('#stationTextMean').text(stationOffMean);
                } else {
                    $('#stationTextMean').text(null);    
                }
                $('#cruText').text(cruOff);
                $('#mapStats').show();
            }
        )
       
    } else if (selectedEventIds.length === allEventIds.length){
        var bbox = checkBoundingBox()[0];
        $.getJSON(window.location+'valiPoints.php?callback=?&mode=2&year='+year+'&month='+month+'&evidHash='+evHash+'&areaView='+bbox,
            function(res){ 
                // check if view is available in db
                var cruOff = (res['cruStats']['regmod']['mean'] - res['cruStats']['cru']['mean']).toFixed(2)
                if(res['stationStats']['mean'] != "null") {
                    var stationOffMean = (res['stationStats']['mean']).toFixed(2);
                 $('#statsText').empty();
                    $('#statsText').append("<center><b><p>Cru Offset: <span id='cruText' style='color:orange'></span> &deg;C, Station Offset: <span id='stationTextMean' style='color:orange'></span> &deg;C</p></b></center>");
                    $('#stationTextMean').text(stationOffMean);
                } else {
                    $('#stationTextMean').text(null);
                    $('#statsText').empty();
                    $('#statsText').append(" <center><b><p>Cru Offset: <span id='cruText' style='color:orange'></span> &deg;C</p></b></center>");
                    $('#stationTextStd').text(null);    
                }
                $('#cruText').text(cruOff);
                $('#mapStats').show();
            }
        )
    } else {
        $('#cruText').text(null);
        $('#stationTextMean').text(null);
    }                               
};       

function addTable(gjsondata, cruStats, stStats) {

    console.log('addTable!!!!')
    console.log(cruStats)
    console.log(stStats)
    var myTableDiv = document.getElementById("jstable");
    var table = document.createElement('TABLE');
    var tableBody = document.createElement('TBODY');

    table.border = '1'
    table.appendChild(tableBody);

    var heading = new Array();
    heading[0] = "Location";
    heading[1] = "Events";
    heading[2] = "Value";
    heading[3] = "CRU offset [\u00B0C]"; 
    if(stStats != '') {
        heading[4] = "Station offset [\u00B0C]";   
    }

    var stock = new Array()
    var mycount = 0;

    if(!gjsondata.features){
        gjsondata =  gjsondata[0]; 
    }

    for(var j = 0; j < gjsondata.features.length; j++){
        var location = geojsonPoints.features[j].properties['location'];
        var flag = 0;   

        if(stock.length > 0){
            for(var i = 0; i < stock.length; i++){
                var stack = String(stock[i][0]);
                if(stack == String(location)){
                    stock[i][1] = 1 + parseInt(stock[i][1]);
                    var tindex = gjsondata.features[j].properties.idx;
                    stock[i][2] += ', ' + tindex;
                    flag = 1;
                }
            }
        }

        if(flag == 0){
            var tindex = gjsondata.features[j].properties.idx;
            var evId = gjsondata.features[j].properties.event_id;

            // populate table
            if(typeof stStats[evId] === "undefined") {
                if(stStats == ""){
                    stock.push(new Array(location, "1", tindex, cruStats[evId]));     
                } else {
                    stock.push(new Array(location, "1", tindex, cruStats[evId],'-'));     
                }
            } else {
                stock.push(new Array(location, "1", tindex, cruStats[evId], stStats[evId]));
            }
        }
        mycount +=1;
    }

    //TABLE COLUMNS
    var cheadingWidth=[90,40,40,80,85]

    var tr = document.createElement('TR');
    tableBody.appendChild(tr);
    for (i = 0; i < heading.length; i++) {
        var th = document.createElement('TH');
        th.width = cheadingWidth[i];
        th.appendChild(document.createTextNode(heading[i]));
        tr.appendChild(th);
    }

    //TABLE ROWS
    for (i = 0; i < stock.length; i++) {
        var tr = document.createElement('TR');
        for (j = 0; j < stock[i].length; j++) {
            var td = document.createElement('TD');
            td.appendChild(document.createTextNode(stock[i][j]));
            tr.appendChild(td);
        }
        tableBody.appendChild(tr);
    }
    $('#jstable table').remove();
    myTableDiv.appendChild(table);

    if(mycount <= 1){
        document.getElementById("eventCount").innerHTML = mycount;
        document.getElementById("eventText").innerHTML = 'event';
    } else {
        document.getElementById("eventCount").innerHTML = mycount;
        document.getElementById("eventText").innerHTML = 'events';
    }

    if(stock.length <= 1){
        document.getElementById("locationCount").innerHTML = stock.length;
        document.getElementById("locationText").innerHTML = 'location';
    }else{
        document.getElementById("locationCount").innerHTML = stock.length;
        document.getElementById("locationText").innerHTML = 'different locations';
    }

    $('.mainText h1').show();
    $('.center').show();
}

function highlightInfoTable(location){
    if (location != 'NULL'){ 
        var arr = [];
        $("#jstable tr").each(function(){
            arr.push($(this).find("td:first").text()); //put elements into array
            var found = $.inArray(location, arr);
            if(found > -1){
                $("#jstable tr").eq(found).addClass('highlight-info');  
            }
        });
    }else{
        $("#jstable tr").removeClass('highlight-info');  
    }
}

function addTable1(geojson, cruStats, stStats) {
    if(geojson !== '' ){
        $('.center h1').remove();
        gjsondata = GeoJSON.parse(geojson, {Point: ['lat', 'lon']});

        var myTableDiv = document.getElementById("jstable");
        var table = document.createElement('TABLE');
        var tableBody = document.createElement('TBODY');

        table.border = '1'
        table.appendChild(tableBody);

        var heading = new Array();
        heading[0] = "Location";
        heading[1] = "Events";
        heading[2] = "Value";
        heading[3] = "CRU offset"; 
        if(stStats != '') {
            heading[4] = "Station offset";   
        }

        var stock = new Array()
        var mycount = 0;

        if(!gjsondata.features){
            gjsondata =  gjsondata[0]; 
        }

        for(var j = 0; j < gjsondata.features.length; j++){
            var location = geojson[j].location;
            var flag = 0;   

            if(stock.length > 0){
                for(var i = 0; i < stock.length; i++){
                    var stack = String(stock[i][0]);
                    if(stack == String(location)){
                        stock[i][1] = 1 + parseInt(stock[i][1]);
                        var tindex = gjsondata.features[j].properties.idx;
                        stock[i][2] += ', ' + tindex;
                        flag = 1;
                    }
                }
            }

            if(flag == 0){
                var tindex = gjsondata.features[j].properties.idx;
                var evId = gjsondata.features[j].properties.event_id;

                // populate table
                if(typeof stStats[evId] === "undefined") {
                    if(stStats == ""){
                        stock.push(new Array(location, "1", tindex, cruStats[evId]));     
                    } else {
                        stock.push(new Array(location, "1", tindex, cruStats[evId],'-'));     
                    }
                } else {
                    stock.push(new Array(location, "1", tindex, cruStats[evId], stStats[evId]));
                }
            }
            mycount +=1;
        }

        //TABLE COLUMNS
        var cheadingWidth=[90,40,40,80,85]

        var tr = document.createElement('TR');
        tableBody.appendChild(tr);
        for (i = 0; i < heading.length; i++) {
            var th = document.createElement('TH');
            th.width = cheadingWidth[i];
            th.appendChild(document.createTextNode(heading[i]));
            tr.appendChild(th);
        }

        //TABLE ROWS
        for (i = 0; i < stock.length; i++) {
            var tr = document.createElement('TR');
            for (j = 0; j < stock[i].length; j++) {
                var td = document.createElement('TD');
                td.appendChild(document.createTextNode(stock[i][j]));
                tr.appendChild(td);
            }
            tableBody.appendChild(tr);
        }
        $('#jstable table').remove();
        myTableDiv.appendChild(table);

        if(mycount <= 1){
            document.getElementById("eventCount").innerHTML = mycount;
            document.getElementById("eventText").innerHTML = 'event';
        } else {
            document.getElementById("eventCount").innerHTML = mycount;
            document.getElementById("eventText").innerHTML = 'events';
        }

        if(stock.length <= 1){
            document.getElementById("locationCount").innerHTML = stock.length;
            document.getElementById("locationText").innerHTML = 'location';
        }else{
            document.getElementById("locationCount").innerHTML = stock.length;
            document.getElementById("locationText").innerHTML = 'different locations';
        }

        $('.mainText').show();
        $('.center').show();

    }  else{
        $('#jstable table').remove();
        $('.mainText').hide();
        $( "<center><h1 style='margin-top:20%;'><i>no data available</i></h1></center>" ).appendTo( ".center" );
    }
}

function highlightInfoTable(location){
    if (location != 'NULL'){ 

        var arr = [];
        $("#jstable tr").each(function(){
            arr.push($(this).find("td:first").text()); //put elements into array
            var found = $.inArray(location, arr);
            if(found > -1){
                $("#jstable tr").eq(found).addClass('highlight-info');  
            }
        });
    }else{
        $("#jstable tr").removeClass('highlight-info');  
    }
}
