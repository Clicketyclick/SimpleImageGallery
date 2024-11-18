# Simple Image Gallery 	

List and display images by folder showing metadata like EXIF and IPTC.

All functionallity is build in PHP on top of a SQLite database.

This is heavyly inspired by [Single File PHP Gallery](https://sye.dk/sfpg/), but build from scratch.
_Single File PHP Gallery_ is a nice out-of-the-box gallery app. But has it's issues with handling larger collection of images.

My own collection holds more than 58,000 images in 3,800 directories (or ~228GB).
And I do NOT want to load that onto a webserver!

## Pros and cons

Pros | cons
---|---
Fast loading	| Manual rebuil and database load
Keeps you originals away from the net	| Max display width and heigt defined by database
Watermarking in database[^watermark] | Requires resizing of ALL images

[^watermark]: If you put a watermark into your display images, the original are unaffected.


## Setup

### Local

```
./rebuild.php
./data.db 
.
├───config
│   └───.flags
├───data
│   ├───2003
│   └───2023
│       ├───..
│       ├───.. 
│       └───..
│           ├───..
│           └───..
├───doc
│   └───html
│       └───search
└───lib
```

### Web

```
./index.php
.
├───config
│   └───.flags
├───data		data.db 
└───lib

```


### Configuration


Group|Key|Example|Note
---|---|---|---
data |
||data_root | ./data/ | 
||image_ext|[jpg,JPEG]
images|
||image_resize_type | resampled | Best, but slow 3,45 sec per image (4K)
||image_resize_type | resized | 1 sec  per image (4K)
||image_resize_type | scale | 0,76 sec per image (4K)
||thumb_max_width|300
||thumb_max_height | 300
||display_max_width | 4200
||display_max_height | 4200
||crop | 0	| 0=off/else on
exif|
||exif_map_tag | See on Google map | 
||exif_map_link_stub | [^google_maps]?q={$lat},{$lon} | External map without zoom
||exif_map_link_stub | [^google_maps]?q={$lat},{$lon}&ll={$lat},{$lon}&z={$zoom} | External map w. zoom
||exif_map_embed_tag | Image map | 
||exif_map_embed_stub | [^google_maps]?q={$lat},{$lon}&output=embed  | Simple embedded map w/o zoom
||exif_map_embed_stub | [^google_maps]?q={$lat},{$lon}&ll={$lat},{$lon}&z={$zoom}&output=embed&hl=en" | Embedded map w zoom
maps|
maps/map_source	| google	| google or osm
maps/map_window_margin|0.0005|
maps/map_types|
maps/map_types/google/|tag|"See on Google map"|
maps/map_types/google/|link_stub|[^google_maps]?q={$lat},{$lon}
maps/map_types/google/|embed_stub|[^google_maps]?q={$lat},{$lon}&ll={$lat},{$lon}&z={$zoom}
maps/map_types/osm/|link_stub|[^osm_maps]#map={$zoom}/{$lat}/{$lon}"	|
maps/map_types/osm/|embed_stub|[^osm_maps]export/embed.html?bbox={$lon_margin_lower}%%2C{$lat_margin_lower}%%2C{$lon_margin_higher}%%2C{$lat_margin_higher}&amp;layer=mapnik"
database | 
||file_name | data.db | Database file
||image_ext | [jpg,JPEG]


[^google_maps]: https://maps.google.com/maps
[^osm_maps]: https://www.openstreetmap.org/

## SQLite

### Create table: images

```sql
CREATE TABLE IF NOT EXISTS images (
    name    TEXT,           -- Display name
    file    TEXT not null,  -- Base file

    source  TEXT,           -- Source path
    path    TEXT not null,  -- Search path

    exif    TEXT,           -- EXIF as JSON
    iptc    TEXT,           -- IPTC as JSON

    thumb   TEXT,            -- Thumb Base64 encoded
    display TEXT,            -- Display Base64 encoded

    PRIMARY KEY (source,file)
);

```

```sql
```







```sql
-- Reset path
-- UPDATE images SET path = source;
-- Remove root dir
UPDATE images SET path = replace (path, './data', '.');
```


### Grouping

```sql
-- Find all directories from 2024-08 Blåvand
SELECT path, count(file) AS files FROM images WHERE path LIKE "./2024/2024-08%_Blåvand" GROUP BY path;
```

```console
┌───────────────────────────┬───────┐
│           path            │ files │
├───────────────────────────┼───────┤
│ ./2024/2024-08-05_Blåvand │ 1     │
│ ./2024/2024-08-10_Blåvand │ 5     │
└───────────────────────────┴───────┘
```

Blåvand
```sql
-- Join in virtual display directory: './2024/2024-08_Blåvand'
UPDATE images SET path = './2024/2024-08_Blåvand' WHERE path LIKE "./2024/2024-08%_Blåvand";
SELECT path, count(file) AS files FROM images WHERE path LIKE "./2024/2024-08%_Blåvand" GROUP BY path;
```

```console
┌────────────────────────┬───────┐
│          path          │ files │
├────────────────────────┼───────┤
│ ./2024/2024-08_Blåvand │ 6     │
└────────────────────────┴───────┘
```


Borgholm
```sql
-- Gouping: Merge all 'Borgholm%' to 'Borgholm'
UPDATE images	SET path = './2023/Öland/Borgholm'
	WHERE path like './2023/Öland/Borgholm%'
;
```

Grouping
```sql

DROP TABLE grouping;
CREATE TABLE IF NOT EXISTS grouping (
	pattern	TEXT,
	destination	TEXT
);

SELECT path, count(file) AS file FROM images GROUP BY path;

┌───────────────────────────┬─────────────┐
│           path            │ files       │
├───────────────────────────┼─────────────┤
│ .                         │ 2           │
│ ./2023/Giza               │ 1           │
│ ./2023/Odense             │ 1           │
│ ./2023/Öland              │ 3           │
│ ./2023/Öland/Borgholm1    │ 3           │
│ ./2023/Öland/Borgholm2    │ 3           │
│ ./2024/2024-08-05_Blåvand │ 1           │
│ ./2024/2024-08-10_Blåvand │ 5           │
└───────────────────────────┴─────────────┘

INSERT OR IGNORE INTO grouping( pattern, destination) VALUES( './2023/Öland/Borgholm%','./2023/Öland/Borgholm' );
INSERT OR IGNORE INTO grouping( pattern, destination) VALUES( './2024/2024-08%_Blåvand','./2024/2024-08_Blåvand' );
SELECT * FROM grouping;
┌─────────────────────────┬────────────────────────┐
│         pattern         │      destination       │
├─────────────────────────┼────────────────────────┤
│ ./2023/Öland/Borgholm%  │ ./2023/Öland/Borgholm  │
│ ./2024/2024-08%_Blåvand │ ./2024/2024-08_Blåvand │
└─────────────────────────┴────────────────────────┘

UPDATE images
SET 
  path=t.destination
FROM (
  SELECT * FROM grouping
) t
WHERE images.path like t.pattern;


SELECT path, count(file) AS Files FROM images GROUP BY path;

┌────────────────────────┬─────────────┐
│          path          │ files       │
├────────────────────────┼─────────────┤
│ .                      │ 2           │
│ ./2023/Giza            │ 1           │
│ ./2023/Odense          │ 1           │
│ ./2023/Öland           │ 3           │
│ ./2023/Öland/Borgholm  │ 6           │
│ ./2024/2024-08_Blåvand │ 6           │
└────────────────────────┴─────────────┘
```






Oldest

```sql
-- Oldest image in path
SELECT path, file FROM images WHERE path LIKE "./data/%"
ORDER BY
  path, file ASC
 LIMIT 1
;
```

Newest

```sql
-- Newest picture in path
SELECT path, file FROM images WHERE path LIKE "./data/%" ORDER BY 
  path, file DESC
 LIMIT 1
;
```
Missing
```
-- .read config/missing.sql
-- Select all images w/o updateded metadata
SELECT count(*) FROM images WHERE thumb IS NULL OR display IS NULL OR exif IS NULL OR iptc IS NULL;
```

Update path

```
UPDATE images SET path = replace (path, './data', './');
UPDATE images SET path = replace (path, '/20', './20');
UPDATE images SET path = replace (path, './20', '/20');

UPDATE images SET source = replace (path, './20', './data/20');




ALTER TABLE images ADD COLUMN source  TEXT;
ALTER TABLE images ADD COLUMN name    TEXT;

UPDATE images SET source = path;
UPDATE images SET path = source;








	-- Update path
	UPDATE images SET path = replace (path, './data', '.');
	-- Grouping
	SELECT DISTINCT path FROM images WHERE path LIKE "./2024/2024-08%_Blåvand";
	UPDATE images SET path = './2024/2024-08_Blåvand' WHERE path LIKE "./2024/2024-08%_Blåvand";
	SELECT path FROM images WHERE path LIKE "./2024/2024-08%_Blåvand";



	-- name -----------------------------
	-- ALTER TABLE images ADD COLUMN name;


.progress 1000
CREATE INDEX idx_name ON images(name);
CREATE INDEX idx_source ON images(source);
CREATE INDEX idx_file ON images(file);
CREATE INDEX idx_path ON images(path);
.progress 1000
SELECT count(name) FROM images WHERE name IS NULL;
UPDATE images SET name = file WHERE name IS NULL;
UPDATE images SET source = path WHERE source IS NULL;



	--UPDATE images SET name = file;
	----------------------------------------------------------------------
	-- Replace any case of '.jpg' length with ''
	UPDATE images  
	SET 
	  name = SUBSTR(name, 0, INSTR(LOWER(name), '.jpg')) || '' || SUBSTR(name, INSTR(LOWER(name), '.jpg')+4)
	WHERE 
	  name LIKE "%.jpg%";
	----------------------------------------------------------------------
	-- Remove date prefix
	-- -- 190xx-xx-xxTxx-xx-xx
	UPDATE images SET name = substr( name, 21 ) WHERE name LIKE '19__-__-__T__-__-__%';
	-- -- 20xx-xx-xxTxx-xx-xx
	UPDATE images SET name = substr( name, 21 ) WHERE name LIKE '20__-__-__T__-__-__%';
	-- 20xx-xx-xx_xx-xx-xx
	UPDATE images SET name = substr( name, 21 ) WHERE name LIKE '20__-__-__\___-__-__%' ESCAPE '\' ;
	-- 20xx-xx-xx_
	UPDATE images SET name = substr( name, 11 ) WHERE name LIKE '20__-__-__\_%' ESCAPE '\' ;
	----------------------------------------------------------------------
	.output name_images.txt
	SELECT name FROM images;
	.output


	SELECT name FROM images2 WHERE length(name) > 15 AND ( name like "19%" OR name like "20%" );
```

### Configuration

```sql
-- Configuration
CREATE TABLE IF NOT EXISTS config (
    key             TEXT NOT NULL,
    value           TEXT NOT NULL,  -- String or JSON
    section         TEXT NOT NULL,  -- Section specific
    language        TEXT default NULL,           -- ISO 639-1 (two-letter)
    note            TEXT default NULL,            -- Internal comment
    PRIMARY KEY ( key, section, language )
);
CREATE INDEX idx_config
ON
    config(key, section, language)
;

-- Default configuration
INSERT INTO "config" VALUES('name','SimpleImageGallery','database',NULL,NULL);
INSERT INTO "config" VALUES('version','00.01','database',NULL,NULL);
INSERT INTO "config" VALUES('release','2024-11-17T22:12:32','database',NULL,NULL);
INSERT INTO "config" VALUES('level','alpha','database',NULL,NULL);
INSERT INTO "config" VALUES('min_image_size_bytes','300','database',NULL,'Minimum image size in bytes');
INSERT INTO "config" VALUES('readme','','database','da',NULL);
INSERT INTO "config" VALUES('readme','','database','en',NULL);
INSERT INTO "config" VALUES('title','Erik's gallery','database','en',NULL);
INSERT INTO "config" VALUES('title','Eriks galleri','database','da',NULL);

```

### search

```sql
CREATE TABLE IF NOT EXISTS search
(
    search_id   TEXT NOT NULL
,   recno       NUMERIC NOT NULL
,   entry       TEXT NOT NULL
,   key         TEXT
);

CREATE TABLE IF NOT EXISTS wordclouds (
    cloudname   TEXT NOT NULL,
    key         TEXT NOT NULL,
    entry       TEXT NOT NULL,
    count       TEXT NOT NULL,
    tag         TEXT NOT NULL,
    norm        TEXT NOT NULL
);

┌──────────────────────────────────────┬───────┬──────────────────────────────┬──────────────────────────────┐
│              search_id               │ recno │            entry             │             key              │
├──────────────────────────────────────┼───────┼──────────────────────────────┼──────────────────────────────┤
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ </pre><pre>                  │ </pre><pre>                  │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ ROW:1                        │ row:1                        │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ ID:50387798-b870970-fa004r:n │ id:50387798-b870970-fa004r:n │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ 004r:n                       │ 004r:n                       │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ 004a:h                       │ 004a:h                       │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ CL:Kontor                    │ cl:kontor                    │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ CL:Fagreol                   │ cl:fagreol                   │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ ISIL:EBP                     │ isil:ebp                     │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ AU:Deleuran, Claus           │ au:deleuran, claus           │
│ e4aea517-f553-415c-ab7a-dc9d7f710fe3 │ 1     │ KW:DM2                       │ kw:dm2                       │
└──────────────────────────────────────┴───────┴──────────────────────────────┴──────────────────────────────┘



/*
CREATE TABLE IF NOT EXISTS wordcloudblobs (
    cloudname   TEXT NOT NULL,
    entry       TEXT NOT NULL
);
*/

SELECT count(*) FROM search;
SELECT count(*) FROM wordclouds;
-- SELECT count(*) FROM wordcloudblobs;

```
