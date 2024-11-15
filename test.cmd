@echo off
cls

for %%a in ( resampled resized scale ) DO (
    ECHO: %%a
    del /f data.db
    php rebuild.php -cfg:images:image_resize_type=%%a  > out.%%a.txt 2>&1
    ::echo >data.db
    copy /y data.db data.%%a.db
)
find "Runtime:" *.txt