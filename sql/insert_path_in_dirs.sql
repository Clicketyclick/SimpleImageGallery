-- insert path in dirs
/*
INSERT OR IGNORE INTO dirs (path) values( './2024');
INSERT OR IGNORE INTO dirs (path) values( './2023');
INSERT OR IGNORE INTO dirs (path) values( './2022');
INSERT OR IGNORE INTO dirs (path) values( './2021');
INSERT OR IGNORE INTO dirs (path) values( './2020');

INSERT OR IGNORE INTO dirs (path) values( './2019');
INSERT OR IGNORE INTO dirs (path) values( './2018');
INSERT OR IGNORE INTO dirs (path) values( './2017');
INSERT OR IGNORE INTO dirs (path) values( './2016');
INSERT OR IGNORE INTO dirs (path) values( './2015');
INSERT OR IGNORE INTO dirs (path) values( './2014');
INSERT OR IGNORE INTO dirs (path) values( './2013');
INSERT OR IGNORE INTO dirs (path) values( './2012');
INSERT OR IGNORE INTO dirs (path) values( './2011');
INSERT OR IGNORE INTO dirs (path) values( './2010');
/*
INSERT OR IGNORE INTO dirs (path) values( './2009');
INSERT OR IGNORE INTO dirs (path) values( './2008');
INSERT OR IGNORE INTO dirs (path) values( './2007');
INSERT OR IGNORE INTO dirs (path) values( './2006');
INSERT OR IGNORE INTO dirs (path) values( './2005');
INSERT OR IGNORE INTO dirs (path) values( './2004');
INSERT OR IGNORE INTO dirs (path) values( './2003');
INSERT OR IGNORE INTO dirs (path) values( './2002');
INSERT OR IGNORE INTO dirs (path) values( './2001');
INSERT OR IGNORE INTO dirs (path) values( './2000');

INSERT OR IGNORE INTO dirs (path) values( './1999');
INSERT OR IGNORE INTO dirs (path) values( './1998');
INSERT OR IGNORE INTO dirs (path) values( './1997');
INSERT OR IGNORE INTO dirs (path) values( './1996');
INSERT OR IGNORE INTO dirs (path) values( './1995');
INSERT OR IGNORE INTO dirs (path) values( './1994');
INSERT OR IGNORE INTO dirs (path) values( './1993');
INSERT OR IGNORE INTO dirs (path) values( './1992');
INSERT OR IGNORE INTO dirs (path) values( './1991');
INSERT OR IGNORE INTO dirs (path) values( './1990');

INSERT OR IGNORE INTO dirs (path) values( './1989');
INSERT OR IGNORE INTO dirs (path) values( './1988');
INSERT OR IGNORE INTO dirs (path) values( './1987');
INSERT OR IGNORE INTO dirs (path) values( './1986');
INSERT OR IGNORE INTO dirs (path) values( './1985');
INSERT OR IGNORE INTO dirs (path) values( './1984');
INSERT OR IGNORE INTO dirs (path) values( './1983');
INSERT OR IGNORE INTO dirs (path) values( './1982');
INSERT OR IGNORE INTO dirs (path) values( './1981');
INSERT OR IGNORE INTO dirs (path) values( './1980');

INSERT OR IGNORE INTO dirs (path) values( './1979');
INSERT OR IGNORE INTO dirs (path) values( './1978');
INSERT OR IGNORE INTO dirs (path) values( './1977');
INSERT OR IGNORE INTO dirs (path) values( './1976');
INSERT OR IGNORE INTO dirs (path) values( './1975');
INSERT OR IGNORE INTO dirs (path) values( './1974');
INSERT OR IGNORE INTO dirs (path) values( './1973');
INSERT OR IGNORE INTO dirs (path) values( './1972');
INSERT OR IGNORE INTO dirs (path) values( './1971');
INSERT OR IGNORE INTO dirs (path) values( './1970');
*/
INSERT
OR REPLACE INTO dirs (path)
SELECT
  DISTINCT path
FROM
  images
ORDER BY
  path DESC
;


SELECT path FROM dirs WHERE path LIKE "./1___";
SELECT path FROM dirs WHERE path LIKE "./2___";
