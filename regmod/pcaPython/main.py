#!/bin/python
# -*- coding: utf-8 -*-
"""
Reconstructs temperature fields based on selected indexfields regression data from db
TODO: Work in progress! Implement try and excepts, substitute hardcoded dimensions (81,161)
Created on Mon May 11 23:00:15 2015

@author: Manuel Beck
"""                  

"""
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# LIBRARIES:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
"""

import numpy as np
import h5py
from sklearn.preprocessing import scale
import psycopg2
import sys
import os
# debug show full numpy array
np.set_printoptions(threshold='nan')

"""
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# FUNCTIONS:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
"""

# import postgres interaction functions
from postgresInt import sendDbQuery, getPgData, getPgRast, geotiff2psql

# import regmod statistic functions and main reconstruct function
from pcaStats import pca, mlr, reconstruct

# import numpy 2 geotiff converting function
from np2geotiff import numpy2geotiff

# import command line arguments validator
from argvValidate import validateArgv

"""
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# CONFIGURATION:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
"""

# path and name of hdf5 cru maps file   
hdf5PATH = '/CRU/mSelInCol_'

# db credentials
dbHost = "localhost"
dbName = "myDBName"
dbUser = "myUser"
dbPass = "myPassword"
        
"""
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# RUN:
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
"""

# validate user input
validateArgv(sys.argv[1:])

# define db connection string
conn = psycopg2.connect("host="+dbHost+" dbname="+dbName+" user="+dbUser+" password="+dbPass)

# get command line arguments
year = sys.argv[1]
month = sys.argv[2]
evIdsList = [int(arg) for arg in sys.argv[3:]]
evIdsStr =  ", ".join(sys.argv[3:])

# get mean std map for month 
query = "Select ST_DUMPVALUES(rast) From temperature_cru_std WHERE month = " + str(month) + ";"   
cruMapStd =  getPgRast(conn, query)   
    
# calculate stdmap overall size cause all raster data are trimed and has to be set to the same extend for calculation
bboxExtent = cruMapStd.shape

# get region_rep => max raster of all carresponding correlation rasters
# change to event id in for visible index points
query = """SELECT  ST_DUMPVALUES(ST_Union(f.rast, 'MAX'))
FROM (SELECT ST_UNION(rast,'MAX') as rast
FROM temperature_monthly_regio_weight 
WHERE event_id IN(""" + evIdsStr + """)
UNION ALL 
SELECT ST_MAKEEMPTYRASTER(rast) as rast
FROM temperature_cru_mean
WHERE month=1) As f"""

regionRepr =  getPgRast(conn, query)   

#query = "Select (ST_METADATA(ST_UNION(rast,'MAX'))).*, ST_DUMPVALUES(ST_UNION(rast,'MAX')) From temperature_monthly_regio_weight WHERE event_id IN(" + evIdsStr + ");"   
#metadata =  getPgRast(conn, query, True, bboxExtent)   
  
# get indices_recon
query = """SELECT  ST_DUMPVALUES(ST_Union(f.rast, 'MAX'))
FROM (SELECT ST_UNION(rast,'MEAN') as rast
FROM temperature_monthly_regio_idxrec 
WHERE event_id IN(""" + evIdsStr + """)
UNION ALL 
SELECT ST_MAKEEMPTYRASTER(rast) as rast
FROM temperature_cru_mean
WHERE month=1) As f"""

#query = "Select (ST_METADATA(ST_UNION(rast,'MEAN'))).*, ST_DUMPVALUES(ST_UNION(rast,'MEAN')) From temperature_monthly_regio_idxrec WHERE  event_id IN(" + evIdsStr + ");"   
#indicesRecon =  getPgRast(conn, query, True, bboxExtent)  
indicesRecon =  getPgRast(conn, query)  

# get weighting
query = """SELECT  ST_DUMPVALUES(ST_Union(f.rast, 'MAX'))
FROM (SELECT ST_UNION(rast,'MEAN') as rast
FROM temperature_monthly_regio_weight 
WHERE event_id IN(""" + evIdsStr + """)
UNION ALL 
SELECT ST_MAKEEMPTYRASTER(rast) as rast
FROM temperature_cru_mean
WHERE month=1) As f"""

#query = "Select (ST_METADATA(ST_UNION(rast,'MEAN'))).*, ST_DUMPVALUES(ST_UNION(rast,'MEAN')) From temperature_monthly_regio_weight WHERE  event_id IN(" + evIdsStr + ");"    
#weighting =  getPgRast(conn, query, True, bboxExtent)   
weighting =  getPgRast(conn, query)   

# get cru maps in columns for month 
# get absolute file path
__location__ = os.path.realpath(
    os.path.join(os.getcwd(), os.path.dirname(__file__)))

# set path                
hdf5 = __location__ + hdf5PATH + str(month) + ".hdf5"

# read file
file    = h5py.File(hdf5, 'r') 
# select dataset
dataset = file['cru_data']
mSelCol  = dataset[()]
file.close()         

# transform region_repr map to column vector
regionReprInCol = regionRepr.reshape(regionRepr.shape[0]*regionRepr.shape[1],1)
# Test
# print np.where(~np.isnan(regionReprInCol))  

# get scoredata
mask = ~np.isnan(regionReprInCol)[:,0]
scoredata = mSelCol[:,mask].T 

# scale and center data (has minimal offset in mean to R/Matlab => floating point 10^-17)
zscore = scale(scoredata, axis= 0, with_mean=True, with_std=True, copy=False).T

# get indices
# query = "Select ST_DUMPVALUES(ST_UNION(rast,'MEAN')) From temperature_recon_idx_4_pca WHERE year = " + str(year) + " and month = " + str(month) + " and event_id IN(" + evIdsStr + ");"    
# indicesRecon =  getPgRast(conn, query)   

indicesRecon = indicesRecon/weighting

# reconstruct temperature field
reconstructed = reconstruct(zscore, regionReprInCol, indicesRecon, mSelCol, cruMapStd) 


# trim array to only data extend

# only data extend
#reconstructed[0][0]=15
ymin = min(np.where(~np.isnan(reconstructed))[0])-1
ymax = max(np.where(~np.isnan(reconstructed))[0])+2
xmin = min(np.where(~np.isnan(reconstructed))[1])-1
xmax = max(np.where(~np.isnan(reconstructed))[1])+2
padding = 2
print 'pad'
print reconstructed[0][0]
#print np.where(~np.isnan(reconstructed))[1]
print xmin, xmax, ymin, ymax
# prepare global data array 
a = np.empty(((ymax-ymin)+padding,(xmax-xmin)+padding,))
a[:] = np.nan
print reconstructed.shape

print reconstructed[ymin:ymax,xmin:xmax].shape
#a[padding/2:(ymax-ymin)+padding/2,padding/2:(xmax-xmin)+padding/2]=reconstructed[ymin:ymax,xmin:xmax]
a=reconstructed[ymin:ymax,xmin:xmax]

#print ymin, ymax,xmin,xmax
yscale = 0.493827160493827
xscale = 0.496894409937888
yscale = 0.493827160493827
xscale = 0.496894409937888
print 'scale'
print yscale, xscale
Yminb = ((yscale*(ymin-padding/2))+30)
Ymaxb = ((yscale*(ymax+padding/2))+30)
Xminb = ((xscale*(xmin-padding/2))-30)
Xmaxb = ((xscale*(xmax+padding/2))-30)



Yminb = ((yscale*(ymin))+30)
Ymaxb = ((yscale*(ymax))+30)
Xminb = ((xscale*(xmin))-30)
Xmaxb = ((xscale*(xmax))-30)
# image min/max lat and lon       
lat = np.array(( Yminb, Ymaxb )) #y
lon = np.array(( Xminb, Xmaxb )) #x
#print lat
#print lon
a[np.isnan(a)] = 9999


# set no data value
reconstructed[np.isnan(reconstructed)] = 9999
a=reconstructed
#print reconstructed.shape
import sys
#sys.exit("Error message")
#lat = np.array(( 30, 70 ))
#lon = np.array(( -30.0, 50.0 ))


# create geotiff
#numpy2geotiff(reconstructed, lat, lon)
numpy2geotiff(a, lat, lon)

# write geotiff to postgres and return event hash for php postgres query
geotiff2psql(conn, year, month, evIdsStr) 

# close db connection
conn.close()

# return event hash for php postres query at [4] cause php can only fetch print and gdal prints also success 
# on file creation and upload to stdout 
#print evHash