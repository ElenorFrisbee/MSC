# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# Parser for ghcnV3 worldwide data. By default this script loads the ghcnV3 
# monthly mean temperature data (unadjusted) for regmod relevant time period
# (everthing up to 1890) and loads it as two tables to defined
# postgres data tables: 
# -temperature_validation_data: station_id, year, month and temperature info
# -temperature_validation_stations: station_id, lat, lon, name, elevation, geom
# and rural info  
#
# modified from RghcnV3 2.9 package which is unsupported and removed from Cran 
# doasnt work anymore; at least on my machine
# source: http://cran.r-project.org/src/contrib/Archive/RghcnV3/
#         - Reference manual: http://www.icesi.edu.co/CRAN/web/packages/RghcnV3/RghcnV3.pdf
#         - GHCNM v3 data docu: ftp://ftp.ncdc.noaa.gov/pub/data/ghcn/v3/README
#
# NOTE: execution only works if executed from source;
# else you have to set the working direktory (for the included source files) explicit
#
# date: 22.05.2015
# author: Manuel Beck
# email: manuelbeck@outlook.com
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#
# CONFIGURATION:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# set table names (tables will be created if not existend)
stationInfoTbl <- "temperature_validation_stations"
temperatureInfoTbl <- "temperature_validation_data" 

# set dataset to parse and upload 
# (see downloadAdresses.R for more data)
source("dataAdresses.R")
dataset <- V3.MEAN.ADJ.URL

#set db credentials
dbHost <- "localhost"
dbUser <- "myDbUser"
dbPass <- "myDbPassword"
dbName <- "myDbName"

# set relevant time period (this is the default time frame for regmod idx data)
selStart <- 1701 
selEnd <- 1890

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# LIBRARIES & FUNCTIONS:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

library(R.utils)
library(RPostgreSQL)

# note: only works if executed from source, else you have to set the working direktory explicit
source("downloadFunctions.R")
source("ReadFiles.R")
source("FileParameters.R")
source("toArray.R")
source("isArray.R")
source("isInventory.R")
source("stationTemp2Db.R")

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# RUN:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

# read data from command line
args=(commandArgs(TRUE))
if(length(args)!=0){
  dbHost <- args[[1]]
  dbName <- args[[2]]
  dbUser <- args[[3]]
  dbPass <- args[[4]]
}

# download selected dataset
files <- downloadV3(url = dataset)
v3Mean <- readV3Data(filename=files$DataFilename, output='Array')
inv <- readInventory(filename=files$InventoryFile)

# select data which is relevant for tambora start = earliest available;
# end = last year with datapoint in regmod
vdata <- windowArray(v3Mean, start = selStart, end = selEnd)
rm(v3Mean)

# stations, months, years
dim(vdata)

# create postgres connection string
con <- dbConnect(PostgreSQL(), host=dbHost, user=dbUser, password=dbPass, dbname=dbName)

# create temperature data table and upload every 5000 entries + rest 
print('uploading station data to db; may take a while')
stationTemp2Db(con, vdata, temperatureInfoTbl, 5000)

# get relevant station data
stationData <- subset(inv, select=c("Id", "Lat", "Lon", "Name", "Elevation", "Rural"))

# set satation data column names 
names(stationData) <- c("station_id", "lat", "lon", "name", "elevation", "rural")

# write station data to database
dbWriteTable(con, stationInfoTbl, stationData, row.names = FALSE, append = FALSE)

# add geometry column with SRID 4326 to station data
dbGetQuery(con, paste("ALTER TABLE ", stationInfoTbl, " ADD COLUMN geom geometry(POINT,4326)", sep=""))

# calculate and write geom from lat lon to station data 
dbGetQuery(con, paste("UPDATE ", stationInfoTbl, " SET geom = ST_SetSRID(ST_MakePoint(lon, lat), 4326)", sep=""))

# create a spatial index for geom in station data table
dbGetQuery(con, paste("CREATE INDEX ", stationInfoTbl, "_geom ON ", stationInfoTbl, " USING GIST(geom)", sep="" ))

# closes the connection
dbDisconnect(con)