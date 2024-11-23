--.read ../sql/update_thumb.sql

-- SELECT DISTINCT path FROM images;

-- Update thumb
SELECT "-- Update thumb";
UPDATE
  dirs
SET
  thumb = (
    -- SELECT name
    SELECT
      thumb
    FROM
      images
    WHERE
      path LIKE dirs.path||'%%'
      --path LIKE dirs.path
    ORDER BY
      path DESC,
      FILE DESC
    LIMIT
      1
  )
-- WHERE images.path = dirs.path
;
/*

    SELECT
      thumb
    FROM
      images
    WHERE
      path LIKE dirs.path
    ORDER BY
      path DESC,
      FILE DESC
    LIMIT
      1
*/