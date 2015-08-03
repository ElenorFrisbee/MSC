import numpy as np

def sendDbQuery(conn, query):
     
    # Open a cursor to perform database operations
    cur = conn.cursor()

    # Query the database and obtain data as Python objects                     
    cur.execute(query)
   
    # commit transaction     
    try:
        conn.commit()
    except:
        print "Cant update table!"
    
    # Close communication with the database
    cur.close()
    
    
def getPgData(conn, query):
     
    # Open a cursor to perform database operations
    cur = conn.cursor()
    
    # Query the database and obtain data as Python objects                     
    cur.execute(query)

    #fetchone(), fetchmany(), fetchall().
    raw =  cur.fetchall()
    res = []
    for itm in raw:
        print itm[0]
        res.append(int(itm[0]))
            
    # Close communication with the database
    cur.close()
    
    return res    

def enlargeNpArrByExtend(metadata, raster, bboxextend, topX = -30, topY=70):

  hight = raster.shape[0]
  width = raster.shape[1]

  # manage raster metadata
  xmin = metadata[0]
  ymax = metadata[1]
  numColumns = metadata[2]
  numRows = metadata[3]                                                                           
  scaleX = metadata[4]
  scaleY = metadata[5]
  
  # prepare global data array 
  a = np.empty((bboxextend[0],bboxextend[1],))
  a[:] = np.nan

  # calculate top left corner coords for raster in new bbox array
  # CAUTION: results rounded cause calculation results in a float and the 
  # int cast of e.g. 30.0 leads to 29
  newXmin = (xmin - topX) / (scaleX)
  newYmax = (topY - ymax) / (scaleY)
 
  # calculate corner coords for raster in new bbox array
  # CAUTION: results rounded cause calculation results in a float and the 
  newXmin = int(round(abs((xmin - topX)) / abs(scaleX)))
  newYmax = int(round(abs((topY - ymax)) / abs(scaleY)))
  newXmax = int(round(newXmin+width))
  newYmin = int(round(newYmax+hight))
  
  print newXmin, newYmax 
  print newXmax, newYmin
  # insert raster in global array  
  a[newYmax:newYmin, newXmin:newXmax] =  np.flipud(raster)
  a = np.flipud(a)
  
  return a
 
                  
      
def getPgRast(conn, query, meta = False, bboxExtent = False):
    # use it like "Select ST_DUMPVALUES(rast) From crumapsmean100 WHERE month = 1;"
  
    # Open a cursor to perform database operations
    cur = conn.cursor()
    
    # Query the database and obtain data as Python objects                     
    cur.execute(query)

    # handle calls for aditional metadata
    if meta:
        #fetchone(), fetchmany(), fetchall().
        raw =  cur.fetchall()[0]

        # get metadata
        metadata = raw[0:9]
        
        # get raster data
        raw = raw[10]
    else:
        #get raster data
        raw =  cur.fetchone()[0]
    
    # print returned datatype
    # print type(raw)

    def pgrastToNumpy(raw):
        # unescape bytea from postgres NOTICE: FORCE DB TO OUTPUT with escape
        # ALTER DATABASE postgres SET bytea_output = 'escape'; 
        # new format (since postgres 9.x is hex escaped which is diffrent to encode)
        
        raw = raw[6:-4]
        raw = raw.split('},{')
        res = []
        for rowRaw in raw:
            rowItmsRaw = rowRaw.split(',')
            rowItms = []
            for itm in rowItmsRaw:
                # check for Nan representation  -1.70000000e+308 == 9999
                if(itm == 'NULL' or float(itm) ==  -1.70000000e+308):
                    rowItms.append(np.nan)
                else:
                    rowItms.append(float(itm))
            res.append(rowItms)
        data = np.asarray(res)
        # flip array upside down
        return np.flipud(data)
        
        '''
        # something like this sould worke for St_AsBinary(rast) but I couldnt figure out the right datatype
        # no one from http://docs.scipy.org/doc/numpy/user/basics.types.html is working correctly...
        define raster dimensions
        w = int(137)
        h = int(117)
        img = []                           
        cdef np.ndarray[double] band =  np.frombuffer(raw, dtype='float16', count=w*h)
        img.append((np.reshape(band, ((h, w)))))        
        #print img[0]
        '''  
     
    # Close communication with the database
    cur.close()
   
    # return postgis raster as numpy array
    if meta:
    
        rastArr = pgrastToNumpy(raw)
        rastArr = enlargeNpArrByExtend(metadata, rastArr, bboxExtent)
        return metadata
    else:
        return pgrastToNumpy(raw)
    
def geotiff2psql(conn, year, month, evIds):    
    ## write geotiff to postgres
    import os
  
    locationTif = '/var/shiny-server/www/datacollectorv2/www/genmaps/temp.tif'
  
    os.system("/usr/bin/raster2pgsql -N 9999 -s 4326 -a " + str(locationTif) + " public.temperature_monthly_recon_live | psql -d regmod -U regmod")

    # create hash from event ids to identify previous calculated fields ('simple approach may be inadequate')
   # eventHash = sum(evIds)+len(evIds)

    # update metadata
    query = "UPDATE temperature_monthly_recon_live SET year = " + str(year) + ", month = " + str(month) + ", event_id_array = '{" + evIds + "}' WHERE event_id_array IS NULL;"
    sendDbQuery(conn, query)

    
''' 
# get mean of data without postgis mean
query = 'SELECT event_id FROM weight_4_pca WHERE year = 1767 and month = 6;' 
evIds = getPgData(query)
regionRepList = []
for i, evId in enumerate(evIds):
    query = "Select ST_DUMPVALUES(rast) From weight_4_pca WHERE event_id ="+str(evId)+";"   
    ress = getPgRast(query)
    print np.isfinite(ress).sum()
    print ress[~np.isnan(ress)].min()
    print ress[~np.isnan(ress)].max()
    regionRepList.append(ress)
    
regionRep = np.dstack(regionRepList) 
print regionRep.shape   
maxval = np.nanmax(regionRep, axis=2)
print np.isfinite(maxval).sum()

'''

