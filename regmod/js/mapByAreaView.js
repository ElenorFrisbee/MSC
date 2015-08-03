function mapAreaView(bbox, map){
    if(typeof this.contourMap !== 'undefined'){
        this.map.removeLayer(this.contourMap);   
        this.contourMap = undefined;   
    } 
    if(typeof this.regioMap !== 'undefined'){
        this.map.removeLayer(this.regioMap);   
        this.regioMap = undefined;      
    } 
    if(typeof this.indexData !== 'undefined'){
        this.map.removeLayer(this.indexData);   
        this.indexData = undefined; 
        this.geojsonIdx = undefined; 
    }                         
    if(typeof this.validationData !== 'undefined'){
        this.map.removeLayer(this.validationData);   
        validationData = undefined;   
    } 
    if(typeof this.contourL !== 'undefined'){
        this.map.removeLayer(this.contourL);   
        contourL = undefined;   
    } 
    if(typeof this.selectedEventIds !== 'undefined'){
        this.selectedEventIds = undefined;  
    } 
    if(typeof this.maplegend !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.maplegend);   
        maplegend = undefined;   
    }
    if(typeof this.regioLegend !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.regioLegend);   
        regioLegend = undefined;   
    } 
    if(typeof this.legend2 !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.legend2);   
        legend2 = undefined;   
    }
    if(typeof this.layerControl !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.layerControl);   
        layerControl = undefined;   
    }   
    if(typeof this.contourImage !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeLayer(this.contourImage);   
        contourImage = undefined;   
    } 
    if(typeof this.geojsonMarker !== 'undefined'){
        this.map.removeLayer(this.geojsonMarker);   
        geojsonMarker = undefined;   
    } 
    if(typeof this.legend3 !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.legend3);   
        legend3 = undefined;   
    }
      if(typeof this.drawControl !== 'undefined'){
        // map.removeLayer(this.legend);
        this.map.removeControl(this.drawControl);   
        drawControl = undefined;   
    } 

    // load basemap
    // $('#map').remove(); 
    $('.center').remove(); 
    $('.right').remove(); 
    $('.twoThird').remove();
    $('#bottom').append('<div class="twoThird"></div>'); 
    map = this.map;
    // drawBarPlot(data)
    /*
    $('.loading').remove(); 
    $('#top').prepend('<div id="map"></div>');
    setMapSize('map', 63);

    map = L.map('map', { zoomControl: false }).setView([55, 10], 4);
    L.tileLayer('http://a.tiles.mapbox.com/v3/jcheng.map-5ebohr46/{z}/{x}/{y}.png', {
    maxZoom: 8,
    minZoom: 2
    }).addTo(map);
    //
    new L.Control.Zoom({ position: 'topright' }).addTo(map);
    */
    // create available data heatmap

    if(bbox.length === 4){
        var bboxJson = JSON.stringify(bbox)

        $.getJSON(window.location+'rasterToPng.php?callback=?&areaCountExtent=1&areaView='+bboxJson,
            function(res){ 
                var dim = res['idxPDat'];
                var imageUrl = 'rasterToPng.php?areaView='+bboxJson;
                var viewBounds = [[bbox[1]['lat'], bbox[2]['lon']], [bbox[0]['lat'], bbox[0]['lon']]];  //  ymax, xmax, ymin, xmin                                                
                var imageBounds = [[dim['ymax'], dim['xmax']], [dim['ymin'], dim['xmin']]];  //  ymax, xmax, ymin, xmin                                                

                var contourImage = L.imageOverlay(imageUrl, imageBounds, {opacity:0.7});
                contourImage.addTo(map);
                // set view to countor Image extent
                map.fitBounds(imageBounds);
        })

    } else {

        $.getJSON(window.location+'rasterToPng.php?callback=?&extent=2',
            function(res){ 
                // check if view is available in db
                var dim = res['idxPDat'];
                var imageUrl = 'rasterToPng.php?areaView=1';

                //   var imageBounds = [[res['idxPDat'][0], res['idxPDat'][1]], [res['idxPDat'][2], res['idxPDat'][3]]];  //  ymax, xmax, ymin, xmin                                                
                var imageBounds = [[dim['ymax'], dim['xmax']], [dim['ymin'], dim['xmin']]];  //  ymax, xmax, ymin, xmin                                                

                // contour image has to be globel if created in query function!
                contourImage = L.imageOverlay(imageUrl, imageBounds, {opacity:0.7});
                contourImage.addTo(map);
                // set view to countor Image extent
                  map.fitBounds(imageBounds);
            }
        )
        // var imageBounds = [[50, 30], [40, 0]];  //  ymax, xmax, ymin, xmin          2 1 4 3                                      
        // var imageBounds = [[bbox['recon_interpol_temp_png_ymax'], bbox['recon_interpol_temp_png_xmax']], [bbox['recon_interpol_temp_png_ymin'], bbox['recon_interpol_temp_png_xmin']]];  //  ymax, xmax, ymin, xmin                                                
        //var imageBounds = [[41.6932432432432, -3.30367647058824], [59.2932432432432, 29.7963235294118]];  //  ymax, xmax, ymin, xmin                                                
    }


    // # # # # # DATA POINTS 

    function getMarkerOptions(feature) {
        return {
            radius: 3+(feature.properties.count/5),
            fillColor: '#ffffbf',
            color: "#000",
            weight: 1,
            opacity: 1,
            fillOpacity: 0.6
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

    function onEachFeature(feature, layer) {
        layer.on({
            click: toggleFeature
        })
    };

    function toggleFeature(e) {
        var layer = e.target;

        if (!L.Browser.ie && !L.Browser.opera) {
            //       layer.bringToFront();
        }

        layer.bindPopup('<b>Location: </b>'+layer.feature.properties.location+'<br><b>Event Count: </b>'+layer.feature.properties.count)
        layer.openPopup();
        setTimeout(function(){ layer.closePopup(); }, 3000);
    }



    // get data points overlay
    if(bbox.length === 4){
        $.getJSON(window.location+'postgresInt.php?callback=?&mode=8&areaView='+bboxJson,
            function(res){ 
                // check if view is available in db
                if(res['idxPDat'] != '' && res['idxPDat'] != ''){           
                    var data = res['idxPDat'];
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

                    // make geojson object from data
                    geojsonPoints = GeoJSON.parse(data, {Point: ['lat', 'lon']});

                    // shortPointTxt = createShortTxt();

                    // add index marker
                    geojsonMarker = L.geoJson(geojsonPoints, {
                        pointToLayer: function (feature, latlng) {
                            return L.circleMarker(latlng, getMarkerOptions(feature));
                        },
                        onEachFeature: onEachFeature
                    });
                    geojsonMarker.addTo(map);

                    // ensures that all circels are hoverable by bringing the smaler ones 
                    // (first to iterate) to front
                    for(layer in geojsonMarker._layers){
                        geojsonMarker._layers[layer].bringToBack() 
                    }                                            

                    addLegend(map,geojsonPoints)
                }   
        });

    } else {

        $.getJSON(window.location+'postgresInt.php?callback=?&mode=7',
            function(res){ 
                // check if view is available in db
                if(res['idxPDat'] != '' && res['idxPDat'] != ''){           
                    var data = res['idxPDat'];
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

                    // make geojson object from data
                    geojsonPoints = GeoJSON.parse(data, {Point: ['lat', 'lon']});

                    // shortPointTxt = createShortTxt();

                    // add index marker
                    geojsonMarker = L.geoJson(geojsonPoints, {
                        pointToLayer: function (feature, latlng) {
                            return L.circleMarker(latlng, getMarkerOptions(feature));
                        },
                        onEachFeature: onEachFeature
                    });
                    geojsonMarker.addTo(map);

                    // ensures that all circels are hoverable by bringing the smaler ones 
                    // (first to iterate) to front
                    for(layer in geojsonMarker._layers){
                        geojsonMarker._layers[layer].bringToBack() 
                    }                                            

                    addLegend(map,geojsonPoints)
                }   
        });


    }


    // # # # # # DRAW STUFF 

    // Initialise the FeatureGroup to store editable layers
    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // Initialise the draw control and pass it the FeatureGroup of editable layers
    drawControl = new L.Control.Draw({
        draw: {
            position: 'topleft',
            polygon: false,
            polyline: false,
            circle: false,
            marker: false
        },
        edit: {
            featureGroup: drawnItems
        }
    });
    map.addControl(drawControl);

    // triggered when a new rectangle has been created.
    map.on('draw:created', function (e) {
        var type = e.layerType,
        layer = e.layer;

        if (type === 'rectangle') {
            var area = L.GeometryUtil.geodesicArea(layer.getLatLngs())
            var latlon = layer.getLatLngs()
            var bbox = [];
            for(var i = 0; i < 4; i++){
                bbox.push({'lat':latlon[i]['lat'],'lon':latlon[i]['lng']})    
            }
            mapAreaView(bbox)

            // update controls with region info
            updateControlsByArea(bbox)

            // save bbox in webbrowser session storage to acess it from everywhere
            // source: http://stackoverflow.com/questions/6193574/save-javascript-objects-in-sessionstorage 
            sessionStorage.setItem('bbox', JSON.stringify(bbox));
        }

        drawnItems.addLayer(layer);
    });

    // triggered when has been edited and saved
    map.on('draw:edited', function (e) {
        var layers = e.layers;
        layers.eachLayer(function (layer) {
            var latlon = layer.getLatLngs()
            alert(latlon);
            //do whatever you want, most likely save back to db
        });
    });


    function addLegend(map,geojsonPoints){

        function createLegend(min, max) {

            function roundNumber(inNumber) {

                return (Math.round(inNumber/10) * 10);  
            }

            legend = L.control( { position: 'bottomright' } );

            legend.onAdd = function(map) {

                var legendContainer = L.DomUtil.create("div", "legend"),  
                symbolsContainer = L.DomUtil.create("div", "symbolsContainer"),
                classes = [min, roundNumber((max-min)/2), roundNumber(max)], 
                legendCircle,  
                diameter,
                diameters = [];  

                L.DomEvent.addListener(legendContainer, 'mousedown', function(e) { L.DomEvent.stopPropagation(e); });  

                $(legendContainer).append("<h2 id='legendTitle'>event count</h2>");
                console.log(classes)
                for (var i = 0; i < classes.length; i++) {  

                    legendCircle = L.DomUtil.create("div", "legendCircle");  
                    diameter =  2*(3+(classes[i]/5)); 
                    diameters.push(diameter);

                    var lastdiameter;

                    if (diameters[i-1]){
                        lastdiameter = diameters[i-1];
                    } else {
                        lastdiameter = 0;
                    };
                    console.log(diameter)
                    $(legendCircle).attr("style", "width: "+diameter+"px; height: "+diameter+
                        "px; margin-left: -"+((diameter+lastdiameter+2)/2)+"px" );


                    $(legendCircle).append("<span class='legendValue'>"+classes[i]+"<span>");


                    $(symbolsContainer).append(legendCircle);    

                };

                $(legendContainer).append(symbolsContainer); 

                return legendContainer; 

            };

            legend.addTo(map);  
        } // end createLegend()

        var result = [];
        for(var i = 0; i<geojsonPoints.features.length; i++){
            result.push(geojsonPoints.features[i].properties.count);
        }
        // get unique values
        result = result.myUnique();
        var min = Math.min.apply(null, result),
        max = Math.max.apply(null, result);

        if(max >= 100){
            createLegend(min, max)
        }




        // legend color gradient
        function getColor(d) {
            return d > 9999 ? '#000000' : 
            d > 0.99 ? '#a50026' : 
            d > 0.98 ? '#d73027' : 
            d > 0.97 ? '#f46d43' : 
            d > 0.96 ? '#fdae61' : 
            d > 0.95 ? '#fee090' : 
            d > 0.94 ? '#ffffbf' : 
            d > 0.93 ? '#e0f3f8' : 
            d > 0.92 ? '#abd9e9' : 
            d > 0.91 ? '#74add1' : 
            d > 0.90 ? '#4575b4' : 
            d > 0.85 ? '#313695' : 
            '#000000';
        }

        legend3 = L.control({position: 'bottomright'});
        legend3.onAdd = function (map) {
            var div = L.DomUtil.create('div', 'info legendHz'),
            grades1 = ['less','' ,'data' ,'' ,'','','more' ,'' ,'' ,'data',''],

            grades = [0.9,0.91,0.92,0.93,0.94,0.95,0.96,0.97,0.98,0.99,1.0],
            grades = grades.sort(function(a,b){return a - b}), 
            grades = grades, 
            labels = [];
            div.innerHTML += '<center><b>data per raster cell</b></center>'

            // loop through our density intervals and generate a label with a colored square for each interval
            // first loop for colored legend boxes
            for (var i = 0; i < grades.length; i++) {
                div.innerHTML +=
                '<span style="background:' + getColor(grades[i]) + '"></span> ';
            }

            // a line break
            div.innerHTML += '<br>';

            // second loop for text
            for (var i = 0; i < grades.length; i++) {
                div.innerHTML +=
                '<label><b>' + grades1[i]+ '</b></label>';
            }
            return div;
        };

        legend3.addTo(map);








    }



    // remove table and monthly stuff
    // $('.mainText').remove();
    // $('#jstable').remove();





    // Add markers to map
    // Font-Awesome markers
    /*
    // Creates a red marker with the coffee icon
    var redMarker = L.AwesomeMarkers.icon({
    icon: 'coffee',
    markerColor: 'red'
    });

    L.marker([50,7], {icon: redMarker}).addTo(map);

    L.marker([50,0], {icon: L.AwesomeMarkers.icon({icon: 'ion-thermometer', markerColor: 'red', prefix: 'fa'}) }).addTo(map);

    // Glyphicons
    L.marker([50,5], {icon: L.AwesomeMarkers.icon({icon: 'cog',  prefix: 'glyphicon', markerColor: 'cadetblue'}) }).addTo(map);





    */









}