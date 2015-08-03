
function drawBarPlot(data,barWidth){  

    // get window height to scale timeline appropriate
    var chartHeight = 25; 
    if(barWidth === 0){
        var chartWidth = 26; 
    } else {
        var chartWidth = barWidth; 

    }

    var height = $(window).height();
    var width = $(window).width();
    chartHeight = (height/100) * chartHeight;
    chartWidth = (width/100) * chartWidth;

    var height = (chartHeight/4)*3,
    margin2 = {top: height+35, right: 10, bottom: 40, left: 20},
    width = chartWidth,
    height2 = chartHeight/4,
    margin = {top: 10, right: 10, bottom:height2+55 , left: 20};

    var parseDate = d3.time.format("%Y-%m-%d").parse;

    var color = "#27ae60";

    var x = d3.time.scale().range([0, width]),
    x2 = d3.time.scale().range([0, width]),
    y = d3.scale.linear().range([height, 0]),
    y2 = d3.scale.linear().range([height2, 0]);

    var xAxis = d3.svg.axis().scale(x).orient("bottom"),
    xAxis2 = d3.svg.axis().scale(x2).orient("bottom"),
    yAxis = d3.svg.axis().scale(y).orient("left");

    var brush = d3.svg.brush()
    .x(x2)
    .on("brush", brushed)
    .on("brushend", brushend);

    var area = d3.svg.area()
    .interpolate("monotone")
    .x(function(d) { return x(d.date); })
    .y0(height)
    .y1(function(d) { return y(d.mean); });

    var line = d3.svg.line()
    .interpolate("linear")
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.mean); });

    var area2 = d3.svg.area()
    .interpolate("monotone")
    .x(function(d) { return x2(d.date); })
    .y0(height2)
    .y1(function(d) { return y2(d.mean); });

    var line2 = d3.svg.line()
    .interpolate("linear")
    .x(function(d) { return x2(d.date); })
    .y(function(d) { return y2(d.mean); });
    if(barWidth === 0){
        var svg = d3.select(".right").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom);
    } else {
         var svg = d3.select(".twoThird").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom);
    }
    svg.append("defs").append("clipPath")
    .attr("id", "clip")
    .append("rect")
    .attr("width", width)
    .attr("height", height);

    var focus = svg.append("g")
    .attr("class", "focus")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var context = svg.append("g")
    .attr("class", "context")
    .attr("transform", "translate(" + margin2.left + "," + margin2.top + ")");

    data.forEach(function(d) {
        d.date = parseDate(d.date);
        d.mean = +d.mean;
    });

    x.domain(d3.extent(data.map(function(d) { return d.date; })));
    y.domain(d3.extent(data, function(d) { return d.mean; }));
    y2.domain(d3.extent(data, function(d) { return d.mean; }));
    x2.domain(x.domain());
    y2.domain(y.domain());

    /* // remove are fill
    focus.append("path")
    .datum(data)
    .attr("class", "area")
    .attr("fill", d3.rgb(color).brighter(2))
    .attr("d", area);
    */

    //    show datapoints  
    var dot1 = svg.append('g').attr('class','dots1').selectAll(".dots1")  
    .data(data)         
    .enter()
    .append("circle")
    .attr("r", 1.0)
    .style("fill", "#fff8ee")    
    .style("opacity", .8)      // set the element opacity
    .style("stroke", "#f93")    // set the line colour
    .style("stroke-width", 0.5) 
    .attr("cx", function(d) { return x2(d.date) })       
    .attr("cy", function(d) { return y2(d.mean) })        

    svg.select(".dots1")
    .attr("transform", "translate(" + margin2.left + "," + margin2.top + ")");


    // show datapoints bottom
    var dot = focus.append('g')  
    dot.attr("clip-path", "url(#clip)");
    dot.selectAll(".dots")
    .data(data)         
    .enter()
    .append("circle")
    .attr('class','dots')
    .attr("r", 2.0)
    .style("fill", "#fff8ee")    
    .style("opacity", .8)      // set the element opacity
    .style("stroke", "#f93")    // set the line colour
    .style("stroke-width", 2.5) 
    .attr("cx", function(d) { return x(d.date) })      
    .attr("cy", function(d) { return y(d.mean) })

    /*       .data(data)
    .enter().append("rect")
    .style("fill", "steelblue")
    .attr("x", function(d) { return x2(d.date); })
    .attr("width",1)
    //  .attr("width", x.rangeBand())
    .attr("y", function(d) { return y2(5); })
    .attr("height", function(d) { return height2-y2(5); });

    */

    svg.select(".dots")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var monthArr = new Array('NONE','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

    // add tipsy tooltips
    $('svg circle').tipsy({ 
        gravity: 'w', 
        html: true, 

        title: function() {
            // var d = this.__data__, c = colors(d.i);
            console.log( this)
            //  $(this).style('fill','blue')
            return this.__data__.date.getUTCFullYear() +' '+monthArr[this.__data__.date.getMonth()]; 
        }
    });

    focus.append("path")
    .datum(data)
    .attr("class", "line")
    .attr("stroke", color)
    .attr("d", line);

    focus.append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0," + height + ")")
    .call(xAxis);

    focus.append("g")
    .attr("class", "y axis")
    .call(yAxis)
    .append("text")
    .attr("transform", "rotate(-90)")
    .attr("y", 6)
    .attr("dy", ".71em")
    .style("text-anchor", "end")
    .text("\u00B0C");
    /*
    context.append("path")
    .datum(data)
    .attr("class", "area")
    .attr("fill", d3.rgb(color).brighter(2))
    .attr("d", area2);
    */
    context.append("path")
    .datum(data)
    .attr("class", "line")
    .attr("stroke", color)
    .attr("d", line2);

    context.append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0," + height2 + ")")
    .call(xAxis2);

    context.append("g")
    .attr("class", "x brush")
    .call(brush)
    .selectAll("rect")
    .attr("y", -6)
    .attr("height", height2 + 7);

    // Statistic lines and labels
    var statisticData = calcMeanSdVar(data);
    var meanData = [{date: data[0].date, mean: statisticData.mean}, 
        {date: data[data.length - 1].date, mean: statisticData.mean}];

    var lineStatistic = d3.svg.line()            
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.mean); });

    focus.append('path')
    .datum(meanData)
    .attr("class", "meanline")
    .attr("d", lineStatistic );

    focus.append("text")
    .attr("x", 10)
    .attr("y", height - margin.top)
    .attr("dy", ".35em")
    .attr("class", "meanline-label")
    .text("Mean: " + statisticData.mean.toFixed(2) );

    var sdMinData = [{date: data[0].date, mean: statisticData.mean - statisticData.deviation}, 
        {date: data[data.length - 1].date, mean: statisticData.mean - statisticData.deviation}];

    focus.append('path')
    .datum(sdMinData)
    .attr("class", "sdline min")
    .attr("d", lineStatistic );

    var sdMaxData = [{date: data[0].date, mean: statisticData.mean + statisticData.deviation}, 
        {date: data[data.length - 1].date, mean: statisticData.mean + statisticData.deviation}];

    focus.append('path')
    .datum(sdMaxData)
    .attr("class", "sdline max")
    .attr("d", lineStatistic );

    focus.append("text")
    .attr("x", (width/4)+55)
    .attr("y", height - margin.top)
    .attr("dy", ".35em")
    .attr("class", "sdline-label")
    .text("Standard Deviation: " + statisticData.deviation.toFixed(2) );

    // More statistic labels
    focus.append("text")
    .attr("x", ((width/3)*2)+40)
    .attr("y", height - margin.top)
    .attr("dy", ".35em")
    .attr("class", "label variance")
    .text("Count: " + eventCount );

    function drawBrush(start, end) {
        // our year will this.innerText
        // define our brush extent to be begin and end of the year

        if(end){
            brush.extent([new Date(start + '-01-01'), new Date(end + '-12-31')])
        } else {
            brush.extent(start)    
        }
        // now draw the brush to match our extent
        // use transition to slow it down so we can see what is happening
        // remove transition so just d3.select(".brush") to just draw
        brush(d3.select(".brush").transition());

        // now fire the brushstart, brushmove, and brushend events
        // remove transition so just d3.select(".brush") to just draw
        brush.event(d3.select(".brush").transition())
    }

    var century = 0; 
    $(document).on('click', '#decade', function() {
        var startYear = parseInt($("#decade #tiles td[class*='clickSel']").attr('title'));
      //  console.log($("#decade #tiles td[class*='clickSel']"))
    
        if(century != startYear){      
            var endYear = startYear + 10;
            // alert($(this).attr('title'));
            drawBrush(startYear, endYear);
            century = startYear;
            decade = 0
        }
    });     

    var decade = 0; 
    $(document).on('click', '#century', function() {
        var startYear = parseInt($("#century #tiles td[class*='clickSel']").attr('title'));
        if(decade != startYear){      
            var endYear = startYear + 99;
            // alert($(this).attr('title'));
            drawBrush(startYear, endYear);
            decade = startYear;
            century = 0
        }
    });


    function brushend() {
        var extent = brush.extent();

        // Retrieve brushed data
        var extentData = data.filter(function(d) { return extent[0] <= d.date && d.date <= extent[1] });

        statisticData = calcMeanSdVar(extentData);
        // get number of events in brush
        eventCount = extentData.length;

        meanData = [{date: extentData[0].date, mean: statisticData.mean}, 
            {date: extentData[extentData.length - 1].date, mean: statisticData.mean}];
        focus.select(".meanline").data([meanData]).attr("d", lineStatistic);
        focus.select(".meanline-label").text("Mean: " + statisticData.mean.toFixed(2)+'\u00B0C ' );

        var sdMaxData = [{date: extentData[0].date, mean: statisticData.mean + statisticData.deviation}, 
            {date: extentData[extentData.length - 1].date, mean: statisticData.mean + statisticData.deviation}];
        focus.select(".max").data([sdMaxData]).attr("d", lineStatistic);

        var sdMinData = [{date: extentData[0].date, mean: statisticData.mean - statisticData.deviation}, 
            {date: extentData[extentData.length - 1].date, mean: statisticData.mean - statisticData.deviation}];
        focus.select(".min").data([sdMinData]).attr("d", lineStatistic);

        focus.select(".sdline-label").text("Std.: " + statisticData.deviation.toFixed(2)+'\u00B0C' );

        focus.select(".variance").text("Count: " + eventCount );
    }


    function calcMeanSdVar(values) {
        var r = {mean: 0, variance: 0, deviation: 0}, t = values.length;
        for(var m, s = 0, l = t; l--; s += parseInt(values[l].mean));
        for(m = r.mean = s / t, l = t, s = 0; l--; s += Math.pow(parseInt(values[l].mean) - m, 2));
        return r.deviation = Math.sqrt(r.variance = s / t), r;
    }

    function brushed() {
        x.domain(brush.empty() ? x2.domain() : brush.extent());
        focus.select(".area").attr("d", area);
        focus.select(".line").attr("d", line);
        focus.select(".x.axis").call(xAxis);   
        dot.selectAll(".dots").attr("cx",function(d){ return x(d.date);}).attr("cy", function(d){ return y(d.mean);});   
    }                     

    // default brush position
    console.log(x.domain())
    drawBrush(x.domain());                            
}    