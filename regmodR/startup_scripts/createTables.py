#!/bin/python
# -*- coding: utf-8 -*-
 
import psycopg2
import sys
import os      


# define data tables (left here for easy interchangeability)
TABLES = {}

# get data from tambora master table for monthly selection
TABLES['tambora_temperature_monthly'] = ("""
    CREATE TABLE tambora_temperature_monthly AS(
        SELECT DISTINCT sub_select_1.id AS event_id,
            sub_select_1.year_begin AS year,
            sub_select_1.month_id_begin AS month,
            sub_select_1.name AS location,
            sub_select_1.location_id,
            st_x(st_centroid(location.geometry)) AS lon,
            st_y(st_centroid(location.geometry)) AS lat,
            st_x(st_centroid(location.geometry)) AS lon_info,
            st_y(st_centroid(location.geometry)) AS lat_info,
            geography(location.geometry)::geography(GeometryCollection,4326) AS geom,
            value.value AS idx,
            regexp_replace(sub_select_1.text, '[\n\r]+'::text, ' '::text, 'g'::text) AS text
           FROM ( SELECT event.id,
                    event.time_begin,
                    event.time_end,
                    date_part('day'::text, event.time_end - event.time_begin)::integer AS time_diff_day,
                    name.location_id,
                    name.name,
                    event.quote_id,
                    quote.text,
                    quote.source_id,
                    event.parameter_id,
                    event.attribute_id,
                    event.value_id,
                    event.year_begin,
                    event.month_id_begin,
                    event.day_id_begin,
                    event.year_end,
                    event.month_id_end,
                    event.day_id_end,
                    event.project_id,
                    event.created_by
                   FROM event
                     JOIN name ON event.name_id = name.id
                     JOIN quote ON event.quote_id = quote.id) sub_select_1
             JOIN location ON sub_select_1.location_id = location.id
             JOIN source ON sub_select_1.source_id = source.id
             LEFT JOIN attribute ON sub_select_1.attribute_id = attribute.id
             LEFT JOIN value ON sub_select_1.value_id = value.id
             LEFT JOIN parameter ON sub_select_1.parameter_id = parameter.id
            WHERE
                sub_select_1.attribute_id = 20 AND
                sub_select_1.month_id_begin <= 12 AND
                sub_select_1.time_diff_day >= 28 AND
                sub_select_1.time_diff_day <= 31 AND
                value.value BETWEEN -3 and 3 AND
                sub_select_1.location_id != 5141) ; 
        """)

# create monthly cru mean data table
TABLES['temperature_cru_mean'] = ("""
    CREATE TABLE temperature_cru_mean (
        month numeric CONSTRAINT crumean UNIQUE,
        rast raster
    );
""")

# create monthly cru std data table
TABLES['temperature_cru_std'] = ("""
    CREATE TABLE temperature_cru_std (
        month numeric CONSTRAINT crumstd UNIQUE,
        rast raster
    );
""")

# create reconstructed index fields table for live pca
TABLES['temperature_monthly_regio_idxrec'] = ("""
    CREATE TABLE temperature_monthly_regio_idxrec (
        event_id numeric CONSTRAINT idxrec UNIQUE,
        rast raster
    );
""")

# create reconstructed regression (weightings) fields table for live pca
TABLES['temperature_monthly_regio_weight'] = ("""
CREATE TABLE temperature_monthly_regio_weight (
  event_id numeric CONSTRAINT weight UNIQUE,
  rast raster
    );
""")

# create table for reconstruction for every event_id
TABLES['temperature_monthly_recon_single'] = ("""
CREATE TABLE temperature_monthly_recon_single (
  event_id numeric CONSTRAINT reconsingle UNIQUE,
  rast raster
    );
""")

# create table for reconstruction for every event_id
TABLES['temperature_monthly_recon'] = ("""
CREATE TABLE temperature_monthly_recon (
  year numeric, 
  month numeric,
  rast raster,
  event_count numeric,
  CONSTRAINT recon UNIQUE (year, month)
    );
""")

# create table for reconstruction for every event_id
TABLES['temperature_monthly_recon_live'] = ("""
CREATE TABLE temperature_monthly_recon_live (
  event_id_array text[],
  year numeric, 
  month numeric,
  rast raster,
  CONSTRAINT live UNIQUE (event_id_array, year, month)
    );
""")

# --contour lines master table
TABLES['teperature_monthly_isotherms'] = ("""
CREATE TABLE teperature_monthly_isotherms
(
  ogc_fid serial NOT NULL,
  wkb_geometry geometry(LineString,4326),
  temp numeric(12,3),
  year numeric(4),
  month numeric(2),
  event_id numeric
);
""")


# get command line arguments
dbHost = sys.argv[1]
dbName = sys.argv[2]
dbUser = sys.argv[3]
dbPass = sys.argv[4]

con = None
 
try:               
    con = psycopg2.connect(host=dbHost, database=dbName, user=dbUser, password=dbPass) 
    cur = con.cursor()
    for name, ddl in TABLES.iteritems():
        try:
            print("Creating table {}: ".format(name))
            cur.execute(ddl)
        except psycopg2.Error as err:
            con.rollback()
            print err.pgerror
        else:
            con.commit()
            print("OK")
    
except psycopg2.DatabaseError, e:
    print 'Error %s' % e                                      
    sys.exit(1)
    
finally:
    if con:
        con.close()
 