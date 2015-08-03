### write numpy array as geoitiff to disk

import sys
import numpy as np
# append path for osgeo gdal to resolve "No module named _gdal_array" conflict
sys.path.insert(0, '/home/manuel/regmod/postgis-cb-env/lib/python2.7/site-packages/')
from osgeo.gdalconst import *
def numpy2geotiff(numpyArr, lat, lon):
    # modified from: http://gis.stackexchange.com/a/37431
    from osgeo import osr
    import gdal
                                 
    # image data
    array = np.flipud(numpyArr)

    # image min/max lat and lon       
    lat = np.array(( 30.0, 70.0 ))
    lon = np.array(( -30.0, 50.0 ))

    # get upper left corner coordinates
    xmin,ymin,xmax,ymax = [lon.min(),lat.min(),lon.max(),lat.max()]
    nrows,ncols = np.shape(array)
    xres = (xmax-xmin)/float(ncols)
    yres = (ymax-ymin)/float(nrows)
    geotransform=(xmin,xres,0,ymax,0, -yres)   
    # That's (top left x, w-e pixel resolution, rotation (0 if North is up), 
    #         top left y, rotation (0 if North is up), n-s pixel resolution)
    
    output_raster = gdal.GetDriverByName('GTiff').Create('/var/shiny-server/www/datacollectorv2/www/genmaps/temp.tif',ncols, nrows, 1 ,gdal.GDT_Float32)  # Open the file
    output_raster.SetGeoTransform(geotransform)  # specify coordinates
    srs = osr.SpatialReference()                 # establish coordinate encoding
    srs.ImportFromEPSG(4326)                     # WGS84 (4326) lat long.
    output_raster.SetProjection( srs.ExportToWkt() )   # export coordinate system to file
    output_raster.GetRasterBand(1).WriteArray(array)   # Writes array to raster
    output_raster.FlushCache()                    # write file