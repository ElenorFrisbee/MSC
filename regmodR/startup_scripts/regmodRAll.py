#!/bin/python
# -*- coding: utf-8 -*-
 
import psycopg2
import sys
import os

# get command line arguments
dbHost = sys.argv[1]
dbName = sys.argv[2]
dbUser = sys.argv[3]
dbPass = sys.argv[4]

con = None
print "run regmodR reconstruction"
try:
    con = psycopg2.connect(host=dbHost, database=dbName, user=dbUser, password=dbPass) 
    cur = con.cursor()
    cur.execute('SELECT DISTINCT year, month from tambora_temperature_monthly ORDER BY year, month')          
#    cur.execute('SELECT DISTINCT year, month FROM tambora_temperature_monthly WHERE year = 1662 and month = 1 LIMIT 1;')          

    rows = cur.fetchall()
          
    for i, row in enumerate(rows):
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        print(i+1, row)
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        year = row[0]
        month = row[1]
        os.system('Rscript mainCL.R ' + `year` + ' ' + `month` + ' 2>&1 | tee mapCreateLogTmp.txt')
        
        # create 'clean' log
        os.system('cat mapCreateLogTmp.txt | grep -i -e SOURCEDATE -i -e ERROR -i -e WARNING -i -e Fehler -i -e debug -i -e EXEC -i -e MeanTemp>> mapCreateLog.txt')
        os.system('echo >> mapCreateLog.txt')
    
    # calculate Cru stats
    # os.system('Rscript regmodR/calcTempStats.R 2>&1 | tee cruStatsLogTmp.txt')
      

except psycopg2.DatabaseError, e:
    print 'Error %s' % e                                      
    sys.exit(1)
    
    
finally:
    
    if con:
        con.close()

print "data has been logged to -> mapCreateLog.txt"