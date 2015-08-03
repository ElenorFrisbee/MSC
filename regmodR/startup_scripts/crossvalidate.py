#!/bin/python
# -*- coding: utf-8 -*-

import psycopg2
from itertools import combinations
import os
import time
import sys


# db credentials
dbHost = "localhost"
dbName = "myDBName"
dbUser = "myUser"
dbPass = "myPassword"

con = None
start_time = time.time()

try:
    con = psycopg2.connect(host=dbHost, database=dbName, user=dbUser, password=dbPass) 
    cur = con.cursor()
    cur.execute("""SELECT AA.year, AA.month, array_agg(event_id) 
    FROM tambora_temperature_monthly AS AA, 
    germany_poly AS BB
        WHERE ST_INTERSECTS(AA.geom,BB.geom)
        AND AA.year > 1760 AND AA.year < 1900
group By AA.year, AA.month
ORDER BY AA.year, AA.month;
""")          
#    cur.execute('SELECT DISTINCT year, month FROM tambora_temperature_monthly WHERE year = 1662 and month = 1 LIMIT 1;')          

    rows = cur.fetchall()
          
    for count, row in enumerate(rows):
        year = row[0]
        month = row[1]
        evids = row[2]
        print year, month
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        print(count+1, str(year), str(month))
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        print evids
       
        i = 1
        while (i <= len(evids)):
            evIdsListCombo = set(combinations(evids,i))
            for combo in evIdsListCombo:
                #comboSearch =  ','.join(element)      
                print combo
               # print  " uniq(sort(array["+str(combo).strip('(,)')+"]";
                print "SELECT (ST_SUMMARYSTATS(ST_CLIP(AA.rast,BB.geom))).mean FROM temperature_monthly_recon_live AS AA, germany_poly AS BB WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+str(combo).strip('(,)')+"]))"
                cur.execute("UPDATE temperature_monthly_recon_live SET mean_germany = (SELECT (ST_SUMMARYSTATS(ST_CLIP(AA.rast,BB.geom))).mean FROM temperature_monthly_recon_live AS AA, germany_poly AS BB WHERE uniq(sort(AA.event_id_array::int[])) = uniq(sort(array["+str(combo).strip('(,)')+"]))) WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+str(combo).strip('(,)')+"]));"); 
                con.commit()    
            i = i+1
        """        
               #cur.execute("SELECT event_id_array FROM temperature_monthly_recon_live WHERE event_id_array = array['"+comboSearch+"'];") 
                print "SELECT event_id_array FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+comboSearch+"]))"
#                cur.execute("SELECT event_id_array FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+comboSearch+"]));") 
                rows = cur.fetchall()
                if not (rows):
                    print rows 
            i = i+1
            """  

except psycopg2.DatabaseError, e:
    print 'Error %s' % e                                      
    sys.exit(1)
    
    
finally:
    
    if con:
        con.close()
        
totalTime = (time.time() - start_time)
minutes = totalTime/60 
seconds = totalTime%60
print("--- %s minutes %s seconds ---" % (int(minutes), int(seconds)))

    