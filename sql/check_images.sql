-- .read ../sql/check_thumb.sql

-- https://stackoverflow.com/a/45906241

.mode box
SELECT "-- Check if thumb is empty";
SELECT path, file,
CASE ifnull(thumb, 'NUL')	-- If NULL set dummy string
  WHEN 'NUL' THEN 'empty'	-- Check for dummy
           ELSE 'ok' 
       END  status,
	   length(display)
FROM   images
ORDER BY path
;

.mode list

