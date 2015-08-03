getR2PSQLPath <- function(){
  # returns path to raster2psql; this depends on used postgis version
  path <- '/usr/bin/'
}

# define temporary tif file path (for postgres cru data import)
getTmpTifPath <- function(){
   path <- '/genMaps/tif/'
}

# define temporary tif file
getTmpTifFile <- function(){
   path <- paste(getwd(),'/genMaps/tif/tmp.tif',sep="")
}

# #getIdxPngPath <- function(){
# #  path <- '/www/genmaps/png/idx/'
# #}
# 
# #getTmpPngPath <- function(){
# #  path <- 'genmaps/png/temperature/'
# #}
# 
# getTmpPngFileName <- function(){
#     fname <- 'reconInterpolTmp'
# }
# 
# getTmpGjFileName <- function(){
#     fname <- 'reconInterpolContourTmp'
# }
# 
# 
# getIdxSaveToTifPath <- function(){
#   path <- '/www/genmaps/tif/idx/'
# }
# 
# getSaveToPngTmpPath <- function(){
#   path <- '/www/genmaps/png/temperature/'
# }
# 
# getSaveToPngIdxPath <- function(){
#   path <- '/www/genmaps/png/idx/'
# }
# 
# getIdxTmpFname <- function(){
#   filename <- 'reconInterpolIdxTmp'  
# }
# 
# getInterpolTmpFname <- function(){
#    filename <- 'reconInterpolTmp'
# }
# 
# getGisFolderPath <- function(){
#   path <- '/www/genmaps/GISFiles/'
# }
# 
# getGeojsonFolderPath <- function(){
#   path <- paste(getwd(), '/www/genmaps/geojson/', sep="")
# }
# 
# getGeojsonFolderDb <- function(){
#     path <- "genmaps/geojson/"
# }


