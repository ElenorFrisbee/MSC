getReanalysisData <- function(month){
  
  # create path to CRU folder
  cruPATH <- gsub("/R/regmod", "", getwd())
    
  # build connection path
  con <- paste(cruPATH, '/CRU/', 'cru_temp_europe_', month, '.RData', sep='')
  
  # load time, longitude, latitude and maps
  load(con) 
  
  return(list(time, longitude, latitude, maps))  
}


