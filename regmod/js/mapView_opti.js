// RUN

function appInit(){
    // load and add basemap
    map = L.map('map', { zoomControl: false }).setView([50, 10], 4);
    addBaseMap(map)
    $('.loading').remove(); 

    // add on click temp info
    addOnClickInfo();

    addOnAddRemoveInfo();

}

function updateApp(selYear, selMonth){
    // disable select menu
    disableMenu();

    // make accesable 
    this.selYear = selYear
    this.selMonth = selMonth

    if(typeof this.contourMap !== 'undefined'){
        map.removeLayer(this.contourMap);   
        this.contourMap = undefined;   
    } 
    if(typeof this.regioMap !== 'undefined'){
        map.removeLayer(this.regioMap);   
        this.regioMap = undefined;      
    } 
    if(typeof this.indexData !== 'undefined'){
        map.removeLayer(this.indexData);   
        this.indexData = undefined; 
        this.geojsonIdx = undefined; 
    }                         
    if(typeof this.validationData !== 'undefined'){
        map.removeLayer(this.validationData);   
        validationData = undefined;   
    } 
    if(typeof this.contourL !== 'undefined'){
        map.removeLayer(this.contourL);   
        contourL = undefined;   
    } 
    if(typeof this.selectedEventIds !== 'undefined'){
        this.selectedEventIds = undefined;  
    } 


    getRasterMap(map, selYear, selMonth, 'contourMap')
    getDataPoints(map, selYear, selMonth, 1, 'indexData')
    getDataPoints(map, selYear, selMonth, 1, 'validationData')
    getRasterMap(map, selYear, selMonth, 'regionMap')

    getContourLines(map, selYear, selMonth)

    // load all data and wait till everything is done, than add controls
    $.when(mapTemperature, pointVali, pointIdx, contourLines, regioMap).done(function(a1,a2,indexData, cdat){
        mapTemperature = pointVali = pointIdx = contourLines = undefined;

        // add temperature legend
        if(cdat[0].features !== null){ 
            geojsondat =  cdat[0]
            addTempLegend(map);
        }

        // add map controls
        addControls(map);

        // add map info with shorttext
        addInfo(map, selYear, selMonth);

        // add info table
        var tableData = indexData[0];
        addTable1(tableData['idxPDat'],  tableData['cruStats'], tableData['stationStats'])

        // re-enable select meanu:
        enableMenu();
    });

    /*

    // update short info
    shortPointTxt = createShortTxt(geojson);


    // populate info table
    if(res['cruStats'] != '' && res['stationStats'] != ''){
    addTable(geojson, res['cruStats'], res['stationStats']);
    }

    */

}


//###############################################################################
function addBaseMap(map){

    L.tileLayer('http://a.tiles.mapbox.com/v3/jcheng.map-5ebohr46/{z}/{x}/{y}.png', {
        maxZoom: 8,
        minZoom: 2
    }).addTo(map);

    // add zoom control
    new L.Control.Zoom({ position: 'topright' }).addTo(map);
}

function addRasterMap(imageUrl, type, imageBounds, viewBounds){

    // load map
    this[type] = L.imageOverlay(imageUrl, imageBounds, {opacity:0.7});

    // display map
    this[type].addTo(map);

    // set view to countor Image extent
    if(typeof viewBounds !== 'undefined'){
        map.fitBounds(viewBounds);
    }

}

function addCircleMarker(map, geojson, type){

    // add index marker
    this[type] = L.geoJson(geojson, {
        pointToLayer: function (feature, latlng) {
            return L.circleMarker(latlng, getMarkerOptions(feature, type));
        },
        onEachFeature: onEachFeature
    });

    if(type !== "validationData"){
        this[type].addTo(map);    
    }

}

function addInfo(map, selYear, selMonth){

    $('.info').remove();

    var selMonthArr = new Array('NONE','January','February','March','April','May','June','July','August','September','October','November','December');

    if(typeof this.geojsonIdx !== 'undefined'){
        var shortPointTxt = createShortTxt(this.geojsonIdx);
    } else {
        var shortPointTxt = undefined;   
    }

    //define info control
    info = L.control({style:style, position: 'bottomleft'});  

    info.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
        this.update();
        return this._div;
    };

    info.update = function (props) {
        if(typeof shortPointTxt === 'undefined'){
            this._div.innerHTML = '<h4>'+selMonthArr[selMonth]+" "+selYear+'</h4>'

        } else {
            this._div.innerHTML = '<h4>'+selMonthArr[selMonth]+" "+selYear+'</h4>' +  (props ?
                'Temperature: ' + props.temp
                : '<i>'+shortPointTxt+'</i>'
            );    
        } 

        highlightInfoTable('NULL') ;
    };

    // show detail info to selected (hover) index point
    info.updatePoint = function (props) {

        var text = props.text;
        var location = props.location;

        highlightInfoTable(location);

        text = replaceAll(text, '*', ''); 
        if(text.length > 9000) text = text.substring(0,9000) +' [...]';
        this._div.innerHTML = '<h4>'+selYear+" "+selMonthArr[selMonth]+'</h4>' +  (props ?
            '<b>Location: </b>' + props.location + '<br />'+
            '<b>Event Id: </b>' + props.event_id + '<br />'+
            '<b>Value: </b>' + props.idx + '<br />'+
            '<b>Longitude: </b>' +  parseFloat(props.lon_info).toFixed(2) + '<br />'+
            '<b>Latitude: </b>' +  parseFloat(props.lat_info).toFixed(2) + '<br />'+
            '<b>Text: </b>' + text + '<br />'
            : '');
    };

    info.addTo(map);

    // modify info style
    var width = $('.left').width()-10;
    var height = $('#map').height();
    height = (height/100) * 80;
    $('.info').css({'max-width':width, 'max-height':height, 'overflow': 'hidden'}); 
}

function addControls(map){ 
    var overlayPane = {};

    if(typeof this.layerControl !== 'undefined'){
        layerControl.removeFrom(map);   
    } 
    if(typeof this.contourMap !== 'undefined'){
        overlayPane['reconstructed temperature map'] = this.contourMap;   
    } 
    if(typeof this.regioMap !== 'undefined'){
        overlayPane['regionalised idices map'] = this.regioMap;   
    } 
    if(typeof this.indexData !== 'undefined'){
        overlayPane['index points'] = this.indexData;   
    } 
    if(typeof this.validationData !== 'undefined'){
        overlayPane['climate stations'] = this.validationData;  
    } 
    if(typeof this.contourL !== 'undefined'){
        overlayPane['contour lines'] = this.contourL;  
    } 
    if(typeof this.selectMap !== 'undefined'){
        overlayPane['reconstructed temperature map'] = this.selectMap;  
    } 


    // Add a layer control element to the map
    layerControl = L.control.layers(null, overlayPane, {position: 'topleft'});
    layerControl.addTo(map);
}

function addContourLines(map, geojson){
    // contourL = L.geoJson(geojson, {style: style, onEachFeature: onEachFeature});
    contourL = L.geoJson(geojson, {style: style});
    // Lgeojson.addTo(map); // disabled by default
}

function updateAppBySelect(){

    if(this.selectedEventIds[0] != null && this.selectedEventIds.length != this.allEventIds.length){

        getRasterMap(map, selYear, selMonth, 'selectMap');
        getRasterMap(map, selYear, selMonth, 'regionMap');
        getDataPoints(map, selYear, selMonth, 1,'validationDataArea');
        updateStats(selectedEventIds, selYear, selMonth);

        // pointVali, pointIdx, contourLines,

        $.when(selMap, regioMap, pointValiArea).done(function(a1,a2,a3){

            // update text stats

            // update controls with new layer info
            addControls(map);
        });

    } else if(this.selectedEventIds.length === this.allEventIds.length) {

        getRasterMap(map, selYear, selMonth, 'contourMap');
        getRasterMap(map, selYear, selMonth, 'regionMap');
        getDataPoints(map, selYear, selMonth, 1, 'validationData');
        updateStats(selectedEventIds, selYear, selMonth);

        // update text stats
        $.when(mapTemperature, regioMap, pointVali).done(function(a1,a2,a3){
            // update controls with new layer info
            addControls(map);
        });

    } else {
        if(typeof this.contourMap !== 'undefined'){
            map.removeLayer(this.contourMap);   
            this.contourMap = undefined;   
        } 
        if(typeof this.selectMap !== 'undefined'){
            map.removeLayer(this.selectMap);   
            this.selectMap = undefined;   
        } 
    }
}

function addOnClickInfo(){
    map.on('click', function(e) {
        //alert("Lat, Lon : " + e.latlng.lat + ", " + e.latlng.lng+"  " + year +" "+month)
        var lat = e.latlng.lat;
        var lon = e.latlng.lng;

        if(selectedEventIds.length === allEventIds.length){
            var bbox = checkBoundingBox()[0]
            $.getJSON(window.location+'rasterToPng.php?callback=?&year='+selYear+"&month="+selMonth+"&lat="+lat+"&lon="+lon+"&evid="+selectedEventIds.toString(),
                function(res){
                    if(res != null){
                        var marker = L.circleMarker([e.latlng.lat,e.latlng.lng]).addTo(map)
                        .bindPopup(parseFloat(res).toFixed(2)+" &deg;C" ).openPopup();
                        setTimeout(function(){ map.removeLayer(marker); }, 1000);
                    }
                    // define rectangle geographical bounds
                    //  var bounds = [[e.latlng.lat, e.latlng.lng], [e.latlng.lat+1, e.latlng.lng+1]];
                    // create an orange rectangle
                    //  L.rectangle(bounds, {color: "#ff7800", weight: 100}).addTo(map);

            }); 
        } else if(selectedEventIds[0] != null && selectedEventIds.length != allEventIds.length){
            var sum = selectedEventIds.reduce(function(a, b) { return parseInt(a) + parseInt(b); }); 
            var evHash = sum/selectedEventIds.length 
            $.getJSON(window.location+'rasterToPng.php?callback=?&year='+selYear+"&month="+selMonth+"&lat="+lat+"&lon="+lon+'&evid='+selectedEventIds.toString(),
                function(res){
                    if(res != null){
                        console.log(res)
                        var marker = L.circleMarker([e.latlng.lat,e.latlng.lng]).addTo(map)
                        .bindPopup(parseFloat(res).toFixed(2)+" &deg;C" ).openPopup();
                        setTimeout(function(){ map.removeLayer(marker); }, 1000);
                    }
            });
        }
    }); 
}

function addOnAddRemoveInfo(){
    map.on('overlayadd', function(e){
        if(e.name==='regionalised idices map'){
            addRegioLegend(map) 
            var tMpaControl = $('span:contains(reconstructed temperature map)').parent().children(':first-child')
            if(tMpaControl.is(':checked')){
                tMpaControl.click();  
            }
        }
    });
    map.on('overlayremove', function(e){
        if(e.name==='regionalised idices map'){
            addTempLegend(map) 
            var tMpaControl = $('span:contains(reconstructed temperature map)').parent().children(':first-child')
            if(!tMpaControl.is(':checked')){
                tMpaControl.click();  
            }
        }
    });
};


//######################################################################################
// create default historical text from shortest historical comment
function createShortTxt(geojsonPoints){
    for(var i = 0; i < geojsonPoints.features.length; i++){
        var pointTxt = geojsonPoints.features[i].properties['text'];
        if(i == 0){
            shortPointTxt = pointTxt; 
        }
        else if(shortPointTxt.length > pointTxt.length){
            shortPointTxt = pointTxt;    
        }
    }

    // cut text to n chars and display selMonth selYear h4
    shortPointTxt = replaceAll(shortPointTxt, '*', ''); 
    if(shortPointTxt.length > 150) shortPointTxt = shortPointTxt.substring(0,150) +' [...]';
    // $('.info').html('<h4>'+ this.selMonthArr[selMonth]+" "+selYear+'</h4><i>'+shortPointTxt+'</i>');

    return shortPointTxt; 
}

function checkSameLatLon(data){
    // check if points exist on same location; make little offset if so
    var checkLoc = [];
    for(var i = 0; i < data.length; i++){
        if($.inArray(data[i]['lon'], checkLoc) != -1){

            // multiply offset for multiple same locations
            var count = checkLoc.reduce(function(n, val) {
                return n + (val === data[i]['lon']);
                }, 0);
            checkLoc.push(data[i]['lon'])
            data[i]['lon'] =  parseFloat(data[i]['lon'])+(0.3*count)  
        } else {
            checkLoc.push(data[i]['lon'])
        }
    }
    return data
}

function getDataPoints(map, selYear, selMonth, bbox, type){
    if(type === 'indexData'){
        // check if bounding box was defined and load data appropriate
        var bbox = checkBoundingBox()[0]
        // get data points overlay
        pointIdx = $.getJSON(window.location+'postgresInt.php?callback=?&mode=3&year='+selYear+'&month='+selMonth+'&areaView='+bbox,
            function(res){ 
                // check if view is available in db
                if(res['idxPDat'] != ''){           
                    var data = res['idxPDat'];

                    // check if points exist on same location; make little offset if so
                    data = checkSameLatLon(data)

                    // make geojson object from data
                    geojsonIdx = GeoJSON.parse(data, {Point: ['lat', 'lon']});

                    // store all available event ids for select
                    getAllEventIds(geojsonIdx)

                    // add index marker
                    addCircleMarker(map, geojsonIdx, type)

                    // populate instant stats
                    updateStats(allEventIds, selYear, selMonth)
                } 
        });

    } else if(type === 'validationData'){

        if(typeof this.validationData !== 'undefined'){
            map.removeLayer(this.validationData);   
            validationData = undefined;   
        } 
        var bbox = checkBoundingBox()[0]
        pointVali = $.getJSON(window.location+'valiPoints.php?callback=?&mode=0&year='+selYear+'&month='+selMonth+'&areaView='+bbox,
            function(res){ 
                // check if view is available in db
                if(res['idxPDat'] != ''){
                    console.log('vali0')
                    // make geojson object from data
                    geojson = GeoJSON.parse(res['idxPDat'], {Point: ['lat', 'lon']});

                    // create station marker overlay
                    addCircleMarker(map, geojson, type) // display station marker overlay 

                }
        });
    } else if(type === 'validationDataArea'){

        if(typeof this.validationData !== 'undefined'){
            map.removeLayer(this.validationData);   
            validationData = undefined; 
        } 

        pointValiArea = $.getJSON(window.location+'valiPoints.php?callback=?&mode=3&year='+selYear+'&month='+selMonth+'&evid='+selectedEventIds.toString(),
            function(res){ 
                // check if view is available in db
                if(res['idxPDat'] != ''){
                    // make geojson object from data
                    geojson = GeoJSON.parse(res['idxPDat'], {Point: ['lat', 'lon']});

                    // create station marker overlay
                    addCircleMarker(map, geojson, 'validationData') // display station marker overlay 

                    //geojsonValiPoints.addTo(map); 

                    // display station offset (eg: -1 means that the reconstructed data are at the station locations by 
                    // average 1 C colder than the station data)      
                    /*
                    if(res['idxPDatMean'] != "null") {
                    $('#stationTextMean').text((res['idxPDatMean']).toFixed(2));
                    $('#mapStats').show();
                    } else {
                    $('#mapStats').hide();    
                    }
                    */
                }
        });
    }
}

function getContourLines(map, selYear, selMonth){
    // load contour data
    contourLines =  $.getJSON(window.location+'test.php?year='+selYear+'&month='+selMonth,
        function(res){ 
            if(res.features !== null){
                addContourLines(map, res)
            }
    });
}

function getRasterMap(map, selYear, selMonth, type){
    if(type === 'contourMap'){

        if(typeof this.contourMap !== 'undefined'){
            map.removeLayer(this.contourMap);   
            this.contourMap = undefined;   
        } 
        if(typeof this.selectMap !== 'undefined'){
            map.removeLayer(this.selectMap);   
            this.selectMap = undefined;   
        } 

        var bbox = checkBoundingBox()[0]
        mapTemperature =  $.getJSON(window.location+'rasterToPng.php?callback=?&extent=1&year='+selYear+'&month='+selMonth,
            function(res){ 

                // check if view is available in db else show cru selMonth mean map
                if(res === 'no data'){
                    var imageUrl = 'rasterToPng.php?crumean=1&month='+selMonth;

                    // place map like corner coordinates
                    var imageBounds = [[70,50],[30, -30]];  //  ymax, xmax, ymin, xmin                                                
                    var viewBounds = [[70,50],[30, -30]];  //  ymax, xmax, ymin, xmin                                                
                } else {
                    var dim = res['idxPDat'];
                    var imageUrl = 'rasterToPng.php?year='+selYear+'&month='+selMonth+'&areaView='+bbox;

                    // place map like corner coordinates
                    var viewBounds = [[dim['ymax'], dim['xmax']], [dim['ymin'], dim['xmin']]];  //  ymax, xmax, ymin, xmin                                                

                    var imageBounds = [[70,50],[30, -30]];  //  ymax, xmax, ymin, xmin           
                }

                addRasterMap(imageUrl, type, imageBounds, viewBounds)

            }
        )
    } else if(type == 'regionMap'){
        // set bbox map url
        var bbox = checkBoundingBox()[0]
        if(typeof this.selectedEventIds !== 'undefined'){
            var imageUrl = 'rasterToPng.php?regiomax=1&evid='+this.selectedEventIds.toString();
        }else {
            var imageUrl = 'rasterToPng.php?regiomax=1&year='+selYear+'&month='+selMonth+'&areaView='+bbox;
        }
        // load map and display
        var imageBounds = [[70,50],[30,-30]];  //  ymax, xmax, ymin, xmin           

        regioMap = L.imageOverlay(imageUrl, imageBounds, {opacity:0.9});
        // regioImage.addTo(map);
    } else if(type === 'selectMap'){

        if(typeof this.selectMap !== 'undefined'){
            map.removeLayer(this.selectMap);   
            this.selectMap = undefined;   
        } 
        if(typeof this.contourMap !== 'undefined'){
            map.removeLayer(this.contourMap);   
            this.contourMap = undefined;   
        } 

        selMap = $.getJSON(window.location+'rasterToPng.php?callback=?&live=2&year='+this.selYear+'&month='+this.selMonth+'&evid='+this.selectedEventIds.toString(),
            function(res){ 

                var imageUrl = 'rasterToPng.php?live=1&evid='+selectedEventIds.toString();

                var imageBounds = [[70,50],[30, -30]];  //  ymax, xmax, ymin, xmin                                                

                addRasterMap(imageUrl, type, imageBounds)
        });
    }
}

function getPythonRasterMap(){
    // rework if available get extend and then load like getRaster map but with evHASH if not get extend -> calc data -> get data like before 
    var imageUrl = 'rasterToPng.php?year='+selYear+'&month='+selMonth+'&evid='+selectedEventIds.toString()+'&evidHash='+evHash;
    var imageBounds = [[70, 50], [30, -30]];  //  ymax, xmax, ymin, xmin                                                
    //var imageBounds = [[41.6932432432432, -3.30367647058824], [59.2932432432432, 29.7963235294118]];  //  ymax, xmax, ymin, xmin                                                
    map.removeLayer(contourImage);
    contourImage = new L.imageOverlay(imageUrl, imageBounds, {opacity:0.7});
    contourImage.addTo(map);


}

//######################################################################################################
// UTILS

function clacEventHash(selectedEventIds){

    var sum = selectedEventIds.reduce(function(a, b) { return parseInt(a) + parseInt(b); }); 
    var evHash = sum/selectedEventIds.length 

    return evHash
}

function updateEvidsSelected(layerId, selectedEventIds){
    // this has to be persistent in some way

    if(jQuery.inArray( layerId, selectedEventIds ) == -1){
        selectedEventIds.push(layerId);
    } else {
        var idx = jQuery.inArray( layerId, selectedEventIds )
        selectedEventIds.splice(idx, 1);
    }  
}

function getAllEventIds(geojson){
    //get all event_id's for this selYear selMonth combi
    allEventIds = [];
    selectedEventIds = [];
    for(var i = 0; i < geojson.features.length; i++){
        allEventIds.push(geojson.features[i].properties['event_id']); 
        // make deep copy cause a = b is just a reference ...
        selectedEventIds = deepCopy(allEventIds); 
    }
}

function disableMenu(){
    $('#century').css({'pointerEvents':'none'});
    $('#decade').css({'pointerEvents':'none'});
    $('#year').css({'pointerEvents':'none'});
    $('#month').css({'pointerEvents':'none'});
}

function enableMenu(){
    $('#century').css({'pointerEvents':'auto'});
    $('#decade').css({'pointerEvents':'auto'});
    $('#year').css({'pointerEvents':'auto'});
    $('#month').css({'pointerEvents':'auto'});
}


//######################################################################################################
// Animation

function toggleFeature(e){
    var layer = e.target;
    // only index points are clickable
    if(layer.feature.properties.text){
        var layerId = layer.feature.properties['event_id'];

        updateEvidsSelected(layerId, selectedEventIds)

        // animate point
        if(layer.options.color != '#FFFFFF') {
            layer.setStyle({
                weight: 1,
                color: '#FFFFFF',
                dashArray: '',
                fillOpacity: 0.7
            }) 

        } else {
            layer.setStyle({
                weight: 1,
                color: '#000',
                dashArray: '',
                fillOpacity: 0.7
            })    
        }



        // update displayd stats for selected layers
        updateStats(selectedEventIds, this.selYear, this.selMonth);

        // update map
        updateAppBySelect()
    }
}

function getMarkerOptions(feature, type) {
    if(type !== "validationData"){
        return {
            radius: 6,
            fillColor: getColor(feature.properties.idx),
            color: "#000",
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8
        }         
    }
    else {
        return {
            radius: 5,
            fillColor: getColor(feature.properties.stdOff),
            color: "#000",
            weight: 1,
            opacity: 1,
            fillOpacity: 0.8
        }
    }
};

// style for point layer  
function getColor(d) {
    return d == '-3' ? '#4575b4' :
    d == '-2'  ? '#91bfdb' :
    d == '-1'  ? '#e0f3f8' :
    d == '0'  ? '#ffffbf' :
    d == '1'  ? '#fee090' :
    d == '2'  ? '#fc8d59' :
    d == '3'  ? '#d73027' :
    '#9F0000';
}

// CONTOUR DATA
function style(feature) {
    return {
        // fillColor: getColor(parseInt(feature.properties.level)),
        weight: 1,
        opacity: 1,
        color: 'white',//getColor(parseInt(feature.properties.level)),
        dashArray: '0',
        fillOpacity: 0.7
    };
}

// add map Interaction
function highlightFeature(e) {
    var layer = e.target;
    // if indexpoint or vali point
    if(layer.feature.properties.text){
        // check if contour layer or point layer 
        if(!layer['_radius']){
            layer.setStyle({
                weight: 2,
                color: '#666',
                dashArray: '',
                fillOpacity: 0.7
            });
        }

        if (!L.Browser.ie && !L.Browser.opera) {
            layer.bringToFront();
        }
        // populate info appropriate to layer feature
        (!layer['_radius']) ? info.update(layer.feature.properties) : info.updatePoint(layer.feature.properties);
    }else {
        // popup for vali point
        layer.bindPopup('<b>Location: </b>'+layer.feature.properties.name+'<br><b>Station Temperature: </b>'+layer.feature.properties.temperature+'<br><b>Recon Temperature: </b>'+parseFloat(layer.feature.properties.temp_recon).toFixed(2))
        layer.openPopup();
    }
}

function resetHighlight(e) {
    var layer = e.target;
    if(layer.feature.properties.text){
        if(!e.target['_radius']) Lgeojson.resetStyle(e.target);
        info.update();
    } else {
        setTimeout(function(){ layer.closePopup(); }, 2000);
    }
}

function onEachFeature(feature, layer) {
    layer.on({
        mouseover: highlightFeature,
        mouseout: resetHighlight,
        click: toggleFeature
    })
};
