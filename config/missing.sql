-- .read config/missing.sql
-- Select all images w/o updateded metadata
SELECT count(*) FROM images WHERE thumb IS NULL OR display IS NULL OR exif IS NULL OR iptc IS NULL;
