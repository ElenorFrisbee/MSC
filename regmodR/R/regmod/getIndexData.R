getIndexData <- function(query_year, query_month){
  
  # prepare sql query
  sqlQuery <- paste('SELECT * from ', getTamboraView(),' Where year = ', query_year, ' and month = ', query_month, ';' , sep='') 
  
  # get data from db
  res <- postgresGetQuery(sqlQuery)
  
  # aggregate results
  indexdatapoints <- cbind(res$lat, res$lon, res$idx, res$event_id)

  # round idx data location to .5 grid
  indexdatapoints[,1:2] <- round(indexdatapoints[,1:2] * 2)/2
  
  return(indexdatapoints)
}