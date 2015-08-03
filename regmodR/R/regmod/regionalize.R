regionalize <- function(idxvalues, maps_sel, longitude, latitude, schwelle_index, schwelle_temp, a1 = .85){
  
  # gridwidth
  grid <- .5
  
  # check idxvalues format
  if(length(idxvalues) != 4 && ncol(idxvalues) != 4){
    reconstructed <- longitude <- maps_sel <- idx_map <- NaN
    # break execution
    stop('no correct idxvalues format passed to regionalize!')                            
  }
  
  # possibility here to shrink the area which will be taken into account for 
  # regression analysis of each index point (faster computing of regression etc. ...)
  # default: no limitations 
  #lat <- c(min(idxvalues[,1])-8, max(idxvalues[,1])+8)
  #lon <- c(min(idxvalues[,2])-12, max(idxvalues[,2])+12)
  lon <- c(min(longitude), max(longitude))
  lat <- c(min(latitude), max(latitude))
  
  # checks selected bounding box against shrinked data bb (only necessary if map size is shrinked). 
  # if selected data point is out of range, set point to data bb and throw warning
  ## bbcomparison(lon, lat, longitude, latitude)
  
  # tests selected maps from R with Matlab in function regionalize
  ## test.maps_sel_Regio(maps_sel, longitude, latitude, maps_sel, lon, lat)
  
  # group idx points by mean for exact same location
  # TODO: dont group; calculate for every point and give him afterwards a slight coordinate offset 
  # to make each point selectable in leaflet
  # idxvalues <- groupby(idxvalues, 3)
  
  # sort values if more than one in asc to ensure right value location assigment
  if(length(idxvalues) > 4){
    idxvalues <- idxvalues[ order(idxvalues[,1], idxvalues[,2]), ]
  } 
  
  # 3d to 2d array by column e.g.: 100 81 161 to 100 13041
  x <- ((idxvalues[,1]-min(latitude))/grid)+1
  y <- ((idxvalues[,2]-min(longitude))/grid)+1
  ufa<-matrix(NA,dim(maps_sel)[1],length(x))
  for(i in 1:length(x)){
    ufa[,i] <- cbind(maps_sel[,x[i],y[i]])
  }
  
  maps_sel_incolumns <- t(apply(aperm(maps_sel),3,c))
  
  # Test above pre korrels values 
  ## test.preKorrels(idxvalues, idx_map, idx_pre, maps_sel_incolumns, ufa, ui)
  
  # calculate correlation of data point with 100 year mean temp fields
  korrels <- cor(ufa,  maps_sel_incolumns, use = "pairwise.complete.obs")
  
  # retransform maps in columns to one map for every column 2d -> 3d array
  korrels_map <- columns2maps(korrels, length(longitude))
  
  # get map with max values from each map layer 3d -> 2d array
  region_repr <- apply(korrels_map, c(2,3), nanmax)
  
  # set grid cells with correlation value minor than schwelle_temp to NaN 
  region_repr[region_repr < schwelle_temp] <- NaN
  
  # transform region_repr map to column vector
  region_repr_columns <- matrix(t(region_repr), length(longitude) * length(latitude), 1)
  
  # calculate mean and Standard deviation from ~100 year monthly cru data
  cruMapMean <- apply(maps_sel, c(2,3), nanmean)
  cruMapStd <- apply(maps_sel, c(2,3), sd)
  
  # idxfield recunstruct for all data points (pca)
  indices_rekon <- array(0, c(nrow(idxvalues), dim(korrels_map)[2], dim(korrels_map)[3]))
  weighting <- array(0, c(nrow(idxvalues), dim(korrels_map)[2], dim(korrels_map)[3]))
  
  # reconstruct temperature by index correlation fields
  print('single field reconstruction') 
  for(i in 1:nrow(idxvalues)){
    region_repr_single <- korrels_map[i,,]
    region_repr_single[region_repr_single < schwelle_index] <- NaN
    
    # for all data points reconstruction
    weighting[i,,] <- region_repr_single
    
    region_repr_single[region_repr_single > 0] <- idxvalues[i,3]
    
    # for all data points reconstruction
    indices_rekon[i,,] <- region_repr_single * weighting[i,,]
    region_repr_columns_single <- matrix(t(region_repr_single), length(longitude) * length(latitude), 1)
    
    # reconstruct temperature filed
    reconres <- reconstruct(idxvalues[i,], region_repr_single, maps_sel_incolumns, region_repr_columns_single, latitude, longitude, cruMapMean, cruMapStd, a1)
    
    # array to raster  
    raster <- toRaster(reconres, longitude, latitude)
    
    # set raster NA value
    raster[is.na(raster)] <- NA
    
    # trim raster to relevant extent
    raster <- trim(raster, padding=1)
    
    # interpolate raster
    # raster <- interpolateRaster(raster, interpolsteps= 0.1)[[2]]
    
    if(!exists('manualExe')){
      # save reconstructed map as clipped png to filesystem and make db entry
      raster2mapView(raster, longitude, latitude, year, month, 'single', idxvalues[i,4], idxvalues[i,3])
      
      # save for live pca
      raster4LivePca(year, month, idxvalues[i,], indices_rekon[i,,], 'idxRecPca', latitude, longitude)
      raster4LivePca(year, month, idxvalues[i,], weighting[i,,], 'weightPca', latitude, longitude)
      ## raster4LivePca(year, month, idxvalues[i,], region_repr_single, 'idxPca', maps_sel_incolumns, region_repr_columns_single, latitude, longitude, cruMapMean, cruMapStd)
    } 
  }
  
  # unify all indexmaps to one by average
  tmp_indices_rekon <- apply(indices_rekon, c(2,3), nanmean)
  tmp_weighting <- apply(weighting, c(2,3), nanmean)
  indices_rekon <- tmp_indices_rekon/tmp_weighting
  
  # remove objects
  rm(tmp_indices_rekon, tmp_weighting)
  
  # test.unifiedMaps(indices_rekon, weighting)
  
  # reconstruct temperature
  reconstructed <- reconstruct(idxvalues, indices_rekon, maps_sel_incolumns, region_repr_columns, latitude, longitude, cruMapMean, cruMapStd, a1)
  
  # to raster object
  raster <- toRaster(reconstructed, longitude, latitude)
  
  # raster <- interpolateRaster(raster, interpolsteps= 0.1)[[2]]
  
  # trim raster only to region with not NA values
  ## ! Doasnt incorparate with postgis mapAlgebra functions for multiple raster calculations
  ## (nearly every spatial raster function)
  ## cause EVERY raster has to be aligned with EACHOTHER during a calculation! 
  ## Huge downside of postgis raster which is discussed since 2012
  ## (http://postgis.17.x6.nabble.com/ST-Mapalgebraexpr-requires-same-alignment-td4645592.html)
  ## and wount be implemented in the near future (devs dont agree with the community in this point)
  ## Every workaround (on database level, ST_Resample, ST_Transform,...) will change underlying data
  ## only out of db solutions possible (gdal_merge.py, manipulating raster on array level,...)
  
  # set raster NA value
  raster[is.na(raster)] <- NA
  
  # trim raster to relevant extent
  raster <- trim(raster, padding=1)
  
  # save reconstructed map as geotiff to filesystem and upload it to postgres as raster datatype
  if(!exists('manualExe')){
    raster2mapView(raster, longitude, latitude, year, month, 'recon', evCount = nrow(idxvalues))
  }
}
