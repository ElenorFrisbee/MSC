# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# Calculates monthly mean and std map from cru data and loads to db
#
# Clear Workspace
#rm(list=ls())
Sys.setlocale(locale="C")
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# get all functions
source(paste(getwd(), '/fullHeader.R', sep=''))

# location window select
# Kerneuropa
lat_window <- c(30, 70)
lon_window <- c(-30, 50)

saveToDb <- function(map, longitude, latitude, tifFileLocation, table, month){
  
  # matrix to raster object
  raster <- toRaster(map, longitude, latitude)
  
  #write geotiff to system
  writeRaster(raster, filename = tifFileLocation, format="GTiff", overwrite=TRUE)
  
  # write geotiff to db as raster
  system(paste(getR2PSQLPath(),'raster2pgsql -s 4326 -a ', tifFileLocation,' public.', table,' | psql -d ', getdbname(),' -U ', getdbuser(), sep=''))
  # update raster metadata
  sqlQuery <- paste('UPDATE  ', table,'  SET  month = ', month, ' WHERE month IS NULL;', sep='')
  
  postgresSendQuery(sqlQuery)
}

for(i in 1:12){
  res_getReanalysesData <- getReanalysisData(i)
  time <- res_getReanalysesData[[1]] # time
  longitude <- res_getReanalysesData[[2]] # longitude
  latitude <- res_getReanalysesData[[3]] # latitude
  maps <- res_getReanalysesData[[4]]
  
  # selectmaps
  res_selectedMaps <- selectmaps(longitude, latitude, maps, lon_window, lat_window)
  maps_sel <- res_selectedMaps[[1]]
  longitude <- res_selectedMaps[[2]]
  latitude <- res_selectedMaps[[3]]
  
  # calculate mean and std map from 100 months
  cruMapMean <- apply(maps_sel, c(2,3), nanmean)
  cruMapStd <- apply(maps_sel, c(2,3), sd)
  
  # write mean data to db
  tifFileLocation <- paste(getwd(), getTmpTifPath(), 'cruMapMean_100_', i, '.tif', sep='')
  saveToDb(cruMapMean, longitude, latitude, tifFileLocation, getdbCruMeanTbl(), i)
  
  # write std data to db
  tifFileLocation <- paste(getwd(), getTmpTifPath(), 'cruMapStd_100_', i, '.tif', sep='')
  saveToDb(cruMapStd, longitude, latitude, tifFileLocation, getdbCruStdTbl(), i)
}