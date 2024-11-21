--  .read ../sql/export_images.sql
.headers on
.mode csv
.output images.csv
SELECT * from images;
.quit