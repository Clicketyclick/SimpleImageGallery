-- .read config/old.sql
-- Oldest image in path
SELECT path, file FROM images WHERE path LIKE "./data/%"
ORDER BY
  path, file ASC
 LIMIT 1
;
