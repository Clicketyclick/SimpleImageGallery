-- .read config/new.sql
-- Newest picture in path
SELECT path, file FROM images WHERE path LIKE "./data/%" ORDER BY 
  path, file DESC
 LIMIT 1
;
