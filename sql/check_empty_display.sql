.output check.txt
SELECT path, file, length(display) FROM images WHERE 100 > length(display) OR '' = display OR display IS NULL;
.output
