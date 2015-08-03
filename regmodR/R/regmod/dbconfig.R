# database connection parameters
# Be awere of that any changed table name here must also be changed in the PHP scripts!

# online
getdbHost <- function(){
  host <- 'myHost'
}

getdbuser <- function(){
  user <- "myDBUser"
}

getdbpsw <- function(){
  password <- "myPassword"
}

getdbname <- function(){
  dbname <- "myUserName"
}

getTamboraView <- function(){
  tamboraView <- "tambora_temperature_monthly"
}

# database table and columns for storing model data

getdbMonthlyTempTbl <- function(){
  dbIdxTbl <- 'temperature_monthly_recon'
}

getContourTempTbl <- function(){
  geojsonTempTbl <- 'teperature_monthly_isotherms'
}

getdbSingleTempTbl <- function(){
  dbIdxTbl <- 'temperature_monthly_recon_single'
}

getdbWeightPcaTbl <- function(){
  dbWeightPcaTbl <- 'temperature_monthly_regio_weight'
}

getdbIdxRecPcaTbl <- function(){
  dbIdxRecPcaTbl <- 'temperature_monthly_regio_idxrec'
}

getdbCruMeanTbl <- function(){
  cruMeanTbl <- 'temperature_cru_mean'
}

getdbCruStdTbl <- function(){
  cruStdTbl <- 'temperature_cru_std'
}

# Temperature stats table
getdbTempStatsTbl <- function(){
  user <- "temperatureStats"
}

getTempStatsEvid <- function(){
  col <- "event_id"
}

getTempStatsCru <- function(){
  col <- "cru_diff_mean"
}
