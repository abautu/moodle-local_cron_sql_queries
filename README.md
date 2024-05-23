# moodle-local_cron_sql_queries

## Description

A local plugin that runs SQL queries on a cron schedule. This plugin can be used to run maintenance queries, or queries that precache data for other uses (e.g. configurable reports plugin).

For better security, the queries can not be edited from the Moodle interface. Instead, the queries are stored as local files by the Moodle administrator.

SQL queries files (with .sql extension) should be stored in the Moodle's data folder, one of this subfolders:
- cron_sql_queries/hourly - queries to be run once per hour
- cron_sql_queries/daily - queries to be run once per day
- cron_sql_queries/weekly - queries to be run once per week
- cron_sql_queries/monthly - queries to be run once per month
Each query file can contain multiple queries, separated by a semicolon. The queries are run in the order they appear in the file.

Table names should be enclosed in curly braces, e.g. {user}, to allow for table prefixing. (Having the 'prefix_' text in front of the table name also works, for compatibility with configurable reports plugin.)

## Installation

1. Copy into Moodle (i.e. copy the cron_sql_queries folder into local/ folder).
2. Install the plugin (i.e. visit admin/index.php page)
3. Copy the .sql files you want to be run into the appropriate subfolder of the Moodle's data folder (e.g. moodledata/cron_sql_queries/hourly).

