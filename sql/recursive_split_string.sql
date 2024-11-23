
/**
 *   @file       recursive_split_string.sql
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-23T22:01:16 / ErBa
 *   @version    2024-11-23T22:01:16
 */


-- [Split a string into rows using pure SQLite](https://stackoverflow.com/a/34663866)

WITH RECURSIVE split(s, last, rest) AS (
  VALUES('', '', './Users/fidel/Desktop/Temp')
  UNION ALL
  SELECT s || substr(rest, 1, 1),
         substr(rest, 1, 1),
         substr(rest, 2)
  FROM split
  WHERE rest <> ''
)
SELECT '['||trim(s, '/')
FROM split
WHERE rest = ''
   OR last = '/'
   ;
