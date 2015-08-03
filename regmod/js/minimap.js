function addminimap(){

    var randomPointfile = new Array('genmaps/426leafletPointTest23_42_02.geojson', 'genmaps/283leafletPointTest00_08_50.geojson', 'genmaps/169leafletPointTest23_06_11.geojson', 'genmaps/144leafletPointTest23_00_42.geojson', 'genmaps/103leafletPointTest23_48_46.geojson', 'genmaps/246leafletPointTest23_47_14.geojson');
    function getRandomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
    var path = randomPointfile[getRandomInt(1,7)-1]
    getPointData(path);
    
    function getPointData(path){  
        return $.ajax({
            type:    "GET",
            url:     path,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            success: function(pointData) {         
                renderLmapMarker(pointData);    
            },
            error:   function() {
                console.log('fail '+randomPointfile[getRandomInt(1,7)-1])
                alert ("Whooops; ajax geojson file loading *fail!");
            }
        });
    }


    function renderLmapMarker(gjsondata){

        var basemap = L.tileLayer('http://a.tiles.mapbox.com/v3/jcheng.map-5ebohr46/{z}/{x}/{y}.png', {
            maxZooom: 18  
        });

        // create L.geoJson formated data for getBounds. to add style parameter we neet the raw data
        var lgeojson = L.geoJson(gjsondata);
        //    console.log(lgeojson._layers[getRandomInt(min, max)]);
        console.log(gjsondata.features[1].properties.location)

        // blob adjustment bbox seems not be needed in point data; check later!
        /*
        var blob = lgeojson.getBounds();
        blob['_southWest']['lng'] = -10.0;
        blob['_northEast']['lat'] = 60.0;
        console.log(blob); 
        */

        // for refreshing map with new data
        //$('#map').remove();
        //$('#top').prepend('<div id="map"></div>')

        var map = L.map('minimap',{
            maxZoom: 13,
            minZoom: 2 
        });

        map.fitBounds(lgeojson.getBounds());

        basemap.addTo(map);

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

        function getMarkerOptions(feature) {
            return {
                radius: 5,
                fillColor: getColor(feature.properties.value_idx),
                color: "#000",
                weight: 1,
                opacity: 1,
                fillOpacity: 0.8
            }
        };

        var geojson;
        geojson = L.geoJson(gjsondata, {
            pointToLayer: function (feature, latlng) {
                return L.circleMarker(latlng, getMarkerOptions(feature));
            },
            onEachFeature: onEachFeature
        });
        geojson.addTo(map);

        var legend = L.control({position: 'bottomright'});
        legend.onAdd = function (map) {

            var div = L.DomUtil.create('div', 'legend'),
            grades = [-3, -2, -1, 0, 1, 2, 3],
            labels = [];
            div.innerHTML += '<b>index value</b><br>'
            // loop through our density intervals and generate a label with a colored square for each interval
            for (var i = 0; i < grades.length; i++) {
                div.innerHTML +=
                '<i style="background:' + getColor(grades[i]) + '"></i> ' +
                grades[i] + '<br>';
            }

            return div;
        };

        // NO LEGEND
        // legend.addTo(map);

        // add map Interaction
        function highlightFeature(e) {
            var layer = e.target;

            layer.setStyle({
                //radius: 7
                /*
                weight: 2,
                color: '#666',
                dashArray: '',
                fillOpacity: 0.7
                */
            }); 

            if (!L.Browser.ie && !L.Browser.opera) {
                layer.bringToFront();
            }

            info.update(layer.feature.properties);
        }

        function resetHighlight(e) {
            geojson.resetStyle(e.target);
            info.update();
        }
        /*
        function zoomToFeature(e) {
        geojson.fitBounds(e.target.getBounds());
        }
        */
        function onEachFeature(feature, layer) {
            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                //click: zoomToFeature
            });
        }

        //Info control
        var info = L.control();
        info.onAdd = function (map) {
            this._div = L.DomUtil.create('div', 'info'); // create a div with a class "info"
            this.update();
            return this._div;
        };                                                       
        
        console.log(Object.keys(lgeojson._layers).length)
       var count = 0,
        
         var randompoint = getRandomInt(1, count+1)
         randompoint = randompoint-1
        // method that we will use to update the control based on feature properties passed
        info.update = function (props) {
            this._div.innerHTML = 
            '<b>' + gjsondata.features[randompoint].properties.location + '</b><br />'
            //'Parameter: ' + lgeojson._layers[1]._latlng.lat.parameter_en + '<br />'+
            //'Value: ' + lgeojson._layers[1]._latlng.lat.value_idx + '<br />'+
            //'Text: DEfekte Eintr&auml;ge in db field text und bibliography ~~ "*" [...] "*" kann nicht ohne weiteres geparsed werden; testhalber ohne<br />'
            ;
        };

        //NO INFO
        info.addTo(map);

        // populate info table 
        addTable(gjsondata);
       
       // console.log('DSS'+Object.keys(lgeojson._layers).length);
       // console.log(getRandomInt(1, Object.keys(lgeojson._layers[i]).length+1));
        var lat = lgeojson._layers[randompoint]._latlng.lat;
        var lon = lgeojson._layers[randompoint]._latlng.lng

        //map.setView(new L.LatLng(lat, lon));
        map.panTo(new L.LatLng(lat, lon));

    }
};