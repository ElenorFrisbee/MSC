
function updateControlsByArea(bbox){ 

    var bboxJson = JSON.stringify(bbox)

    // get Timeline Data and preselect (brush) century
    var loc = window.location.pathname;
    var dir = loc.substring(0, loc.lastIndexOf('/'));
    $.getJSON(dir+'/timeline/getTimeline.php?callback=?&areaView='+bboxJson,
        function(res){ 
            //  $('.right svg').remove();
            drawBarPlot(res, 63, function(){
                drawBrush(1700, 1799);    
            });
        }
    ) 

    // populate select 
    var loc = window.location.pathname;
    var dir = loc.substring(0, loc.lastIndexOf('/'));
    $.getJSON(dir+'/tileSelection/getTilesData.php?callback=?&mode=12&areaView='+bboxJson,
        function(res){ 
            // make no click init
            sessionStorage.setItem('noclick', 1);
            createTileSelect1(res, 'century', '');

    });

}  

function addAreaTimeline(bbox){
    var bbox = JSON.parse(bbox);
    var bboxJson = JSON.stringify(bbox)

    // get Timeline Data and preselect (brush) century
    var loc = window.location.pathname;
    var dir = loc.substring(0, loc.lastIndexOf('/'));
    $.getJSON(dir+'/timeline/getTimeline.php?callback=?&areaView='+bboxJson,
        function(res){ 
            //  $('.right svg').remove();
            drawBarPlot(res, 0,function(){
                drawBrush(1000, 1899);    
            });
        }
    ) 
} 

