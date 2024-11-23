--SELECT * FROM dirs;

-- data to show_thumb
SELECT path, thumb FROM dirs WHERE path = './2023/Öland';
SELECT path FROM dirs WHERE path = './2023/Öland';
SELECT thumb, path FROM dirs WHERE path = './2024/2024-08-05_10_Blåvand';
SELECT thumb, path FROM dirs WHERE path = './2023';
SELECT thumb, path FROM dirs WHERE path = './2022';
SELECT thumb, path FROM dirs WHERE path = './2004';
SELECT thumb, path FROM dirs WHERE path = 'W:/gallery_test';
