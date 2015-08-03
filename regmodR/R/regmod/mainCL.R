# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# main execution file for regmodR to call from command line like:
# mainCL.R 1740 1
#
# date: 10.3.2015
# author: Manuel Beck
# email: manuelbeck@outlook.com
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# kill opened db connections if something has crashed 
# library(DBI)
# lapply(dbListConnections(PostgreSQL()), dbDisconnect)

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# INCLUDE LIBRARYS & FUNCTIONS:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# includes all regmodR functions and all necessary librarys
source(paste(getwd(), '/fullHeader.R', sep=''))

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# MAIN:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # 

runRegmod <- function(year, month){
  a1 <- .85
  a2 <- .9
  a3 <- .9
  lat_window <- c(30, 70)
  lon_window <- c(-30, 50)
  
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

# read in the arguments listed at the command line
args=(commandArgs(TRUE))

# args is now a list of character vectors
# First check to see if arguments are passed.
# Then cycle through each element of the list and evaluate the expressions.
if(length(args)==0){
  
  print("No arguments supplied")
  
}else{
  
  # parseCommandArgs()
  # save to global scope
  year <<- as.numeric(args[[1]])
  month <<- as.numeric(args[[2]])
  
  # execute
  execTime <- proc.time()
  runRegmod(year, month)
  
  # calculate and log execution time
  execTime <- proc.time() - execTime
  print(paste("EXEC TIME: ", execTime['elapsed']))
}
