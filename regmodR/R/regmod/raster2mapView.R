raster2mapView <- function(raster, longitude_sel, latitude_sel, year, month, viewType, event_id = 0, evCount = NULL){
  
  # write raster as geotiff to file
  writeRaster(raster, NAflag=9999, filename = getTmpTifFile(), format="GTiff", overwrite=TRUE)

  # check if raster has gradient for multiple contour lines
  if(sum(!is.na(values(raster))) >= 1){
    # load contour to db
    saveContourtoGJson(getTmpTifFile(), year, month, event_id)
  }
  
  # write to db  
  if(viewType == 'recon'){
    # create png for monthly temp file browsing
    writeViewToDB(year, month, viewType, getTmpTifFile(), evCount = evCount) 
    
  }else if(viewType == 'single'){
    writeViewToDB(year, month, viewType, getTmpTifFile(), event_id = event_id) 
  }
  
}