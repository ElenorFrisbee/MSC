# # # # # # # # 
# # Reset DB Connections
# library(DBI)
# lapply(dbListConnections(PostgreSQL()), dbDisconnect)

Sys.setlocale(locale="C")

# # # # # # # #  

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# CONFIGURATION:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# manual execution?
manualExe <<- TRUE

if(manualExe){
  # test.maps_sel(maps, month) // cru files R matrix test against .mat data 
  source(paste('/var/www/vhosts/default/htdocs/regmodR/R/tests/tests.R', sep=''))
  
  setwd('/var/www/vhosts/default/htdocs/regmodR/R/regmod')
  # SELECT month and year
  year <<-  1767
  month <<- 6
  
  # location window select 
  # Kerneuropa
  lat_window <- c(30, 70)
  lon_window <- c(-30, 50)
}

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# INCLUDE LIBRARYS & FUNCTIONS:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# includes all regmodR functions and all necessary librarys
source(paste(getwd(), '/fullHeader.R', sep=''))

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# MAIN:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # 

runRegmod <- function(year, month, lat_window, lon_window){
  a1 <- .85
  a2 <- .9
  a3 <- .9
  
  # paste year month for log file
  print(paste('sourceDate: ',year, ' ', month))
  
  # get index data
  indexData <- getIndexData(year, month)
  
  # get cru reanalysis data for month
  res_getReanalysisData <- getReanalysisData(month)
  time <- res_getReanalysisData[[1]] 
  longitude <- res_getReanalysisData[[2]] 
  latitude <- res_getReanalysisData[[3]]
  maps <- res_getReanalysisData[[4]] 
  
  # DEBUG CHECK maps
  # print('summary reanalyses')
  # test.maps_sel(maps, month)
  
  # trim map by window bounding box
  res_selectedMaps <- selectmaps(longitude, latitude, maps, lon_window, lat_window)
  maps_sel <- res_selectedMaps[[1]]
  longitude_sel <- res_selectedMaps[[2]]
  latitude_sel <- res_selectedMaps[[3]] 
  
  # if all indexpoint values are zero => no differences to cru data than just output cru data;
  # else reconstruct data
  print('regionalize')
  
  # regionalize and reconstruct data
  # TODO: get reconstruct function out of regionalize
  regionalize(indexData, maps_sel, longitude_sel, latitude_sel, a3, a2, a1) 
  
}

if(manualExe){
  runRegmod(year, month, lat_window, lon_window)
}