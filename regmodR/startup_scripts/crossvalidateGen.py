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
    cur.execute("""SELECT AA.year, AA.month, array_to_string(array_agg(BB.event_id),',') AS event_ids 
    FROM temperature_monthly_recon AS AA 
    INNER JOIN tambora_temperature_monthly AS BB ON AA.year= BB.year AND AA.month = BB.month
    WHERE AA.year = 1658
    GROUP BY AA.year, AA.month
    ORDER BY AA.year, AA.month;""")          
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
       
        evIdsList = evids.split(',')
        i = 1

        while (i <= len(evIdsList)):
            evIdsListCombo = set(combinations(evIdsList,i))
            for element in evIdsListCombo:
                combo =  ' '.join(element)      
                comboSearch =  ','.join(element)      
                
               # cur.execute("SELECT event_id_array FROM temperature_monthly_recon_live WHERE event_id_array = array['"+comboSearch+"'];") 
                print "SELECT event_id_array FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+comboSearch+"]))"
                cur.execute("SELECT event_id_array FROM temperature_monthly_recon_live WHERE uniq(sort(event_id_array::int[])) = uniq(sort(array["+comboSearch+"]));") 
                rows = cur.fetchall()
                if not (rows):
                    print rows 
                    os.system('python /var/www/vhosts/default/htdocs/regmod/pcaPython/main.py ' + str(year) + ' ' + str(month) + ' ' + combo)
            i = i+1
      

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

    