
SELECT "-- .read ../sql/create_dirs.sql";

DROP TABLE IF EXISTS dirs;

CREATE TABLE IF NOT EXISTS dirs
(
	path	TEXT PRIMARY KEY,
	thumb	TEXT
)
;

------------------------------------------------------------------------

CREATE INDEX idx_dirs
ON dirs
(
	path
);

.schema dirs
SELECT "done";