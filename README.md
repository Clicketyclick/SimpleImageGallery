# Simple Image Gallery

List and display images by folder showing metadata like EXIF and IPTC.

All functionallity is build in PHP on top of a SQLite database.

This is heavyly inspired by [Single File PHP Gallery](https://sye.dk/sfpg/) but build from scratch.



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
||exif_map_link_stub | [^1]?q={$lat},{$lon} | External map without zoom
||exif_map_link_stub | [^1]?q={$lat},{$lon}&ll={$lat},{$lon}&z={$zoom} | External map w. zoom
||exif_map_embed_tag | Image map | 
||exif_map_embed_stub | [^1]?q={$lat},{$lon}&output=embed  | Simple embedded map w/o zoom
||exif_map_embed_stub | [^1]?q={$lat},{$lon}&ll={$lat},{$lon}&z={$zoom}&output=embed&hl=en" | Embedded map w zoom
database | 
||file_name | data.db | Database file
||image_ext | [jpg,JPEG]

[^1]: https://maps.google.com/maps


## SQLite

```sql
-- Gouping: Merge all 'Borgholm%' to 'Borgholm'
UPDATE images
SET path = './data/2023/Öland/Borgholm'
WHERE path like './data/2023/Öland/Borgholm%'
;
```


```sql
-- Oldest image in path
SELECT path, file FROM images WHERE path LIKE "./data/%"
ORDER BY
  path, file ASC
 LIMIT 1
;
```

```sql
-- Newest picture in path
SELECT path, file FROM images WHERE path LIKE "./data/%" ORDER BY 
  path, file DESC
 LIMIT 1
;
```

