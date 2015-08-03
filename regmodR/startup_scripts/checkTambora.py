#!/bin/python
# -*- coding: utf-8 -*-
 
import psycopg2
import sys
import os      

# define queries
QUERIES = {}

# delete not land surface data
QUERIES['delete_not_land_surface_data'] = ("""
DELETE FROM tambora_temperature_monthly WHERE event_id in (SELECT event_id FROM tambora_temperature_monthly AS AA
    LEFT JOIN world_coastline_50m_poly as BB
    ON ST_Intersects(ST_SetSRID(ST_MakePoint(AA.lon,AA.lat),4326),BB.geom)
WHERE BB.gid is null
AND AA.location_id != 7902);
""")

# delete events outside central europe
QUERIES['delete_outside_central_eu'] = ("""
DELETE FROM tambora_temperature_monthly WHERE event_id not in (SELECT event_id FROM tambora_temperature_monthly AS AA
    LEFT JOIN temperature_cru_mean as BB
    ON ST_Intersects(ST_SetSRID(ST_MakePoint(AA.lon,AA.lat),4326),BB.rast)
WHERE BB.month = 1
AND AA.location_id != 7902);
""")

# delete world poly shape 
QUERIES['drop_world_poly'] = ("""
DROP TABLE world_coastline_50m_poly;
""")


# get command line arguments
dbHost = sys.argv[1]
dbName = sys.argv[2]
dbUser = sys.argv[3]
dbPass = sys.argv[4]
shpFile = sys.argv[5]
con = None
 
# upload world polygon vector data to db
os.system("/usr/bin/shp2pgsql -s 4326 " + str(shpFile) + " public.world_coastline_50m_poly | psql -d "+dbName+" -U "+dbUser)

try:               
    con = psycopg2.connect(host=dbHost, database=dbName, user=dbUser, password=dbPass) 
    cur = con.cursor()
    for name, ddl in QUERIES.iteritems():
        try:
            print("Execute Query {}: ".format(name))
            cur.execute(ddl)
        except psycopg2.Error as err:
            con.rollback()
            print err.pgerror
        else:
            print("OK")
    
except psycopg2.DatabaseError, e:
    print 'Error %s' % e                                      
    sys.exit(1)
    
finally:
    if con:
        con.commit()
        con.close()