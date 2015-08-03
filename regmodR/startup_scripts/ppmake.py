#!/bin/python
# -*- coding: utf-8 -*-
# from http://zetcode.com/db/postgresqlpythontutorial/
 
import psycopg2
import sys
import os

con = None

try:
     
    con = psycopg2.connect(database='myDb', user='myUser') 
    cur = con.cursor()
    cur.execute('SELECT  year_begin, month_id_begin from tambora_temperature_monthly ORDER BY year_begin')          

    rows = cur.fetchall()
          
    for i, row in enumerate(rows):
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        print(i+2, row)
        print('\n# # # # # # # # # # # # # # # # # # # # # # # # # #\n')
        year = row[0]
        month = row[1]
        os.system('Rscript regmodR/mainCL.R ' + `year` + ' ' + `month` + ' 2>&1 | tee mapCreateLogTmp.txt')
        os.system('sudo chmod o+w www/genmaps/GISFiles/clipped_world_poly*')
        
        # create 'clean' log
        os.system('cat mapCreateLogTmp.txt | grep -i -e SOURCEDATE -i -e ERROR -i -e WARNING -i -e Fehler -i -e debug -i -e EXEC -i -e MeanTemp>> mapCreateLog.txt')
        os.system('echo >> mapCreateLog.txt')
    
    # calculate Cru stats
    os.system('Rscript regmodR/calcTempStats.R 2>&1 | tee cruStatsLogTmp.txt')
      

except psycopg2.DatabaseError, e:
    print 'Error %s' % e                                      
    sys.exit(1)
    
    
finally:
    
    if con:
        con.close()
 