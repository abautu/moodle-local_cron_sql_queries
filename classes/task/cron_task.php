<?php

/**
 * Query runner class.
 *
 * @package    local_cron_sql_queries
 * @subpackage classes/task
 */
namespace local_cron_sql_queries\task;

defined('MOODLE_INTERNAL') || die();

/**
 * A schedule task for cron_sql_queries plugin.
 *
 * @package   local_cron_sql_queries
 * @copyright 2024 Andrei Bautu <abautu@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {
    use \core\task\logging_trait;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_cron_sql_queries');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        // pairs of foldername => time delay in seconds
        $folders = [
            'hourly' => 3600, // 60
            'daily' => 86400, // 24 hours
            'weekly' => 604800, // 7 days
            'monthly' => 2592000, // 30 days
        ];

        $this->log_start("Start processing cron SQL queries.", 0);

        foreach($folders as $folder => $delay) {
            $this->process_folder($folder, $delay);
        }

        $this->log_finish("Done processing cron SQL queries.", 0);
    }

    /**
     * Process a folder with query files.
     *
     * @param string $folder Folder name.
     * @param int $delay Time delay in seconds between succesive runs of each file.
     * @return void
     */
    protected function process_folder($folder, $delay) {
        global $CFG;

        $this->log_start("Processing folder $folder", 1);
        $path = "{$CFG->dataroot}/cron_sql_queries/";
        // Search for SQL files in the folder.
        foreach(glob("{$path}{$folder}/*.sql") as $filepath) {
            $queryname = str_replace($path, '', $filepath);
            // Skip files that have been processed recently.
            $nextrun = (int) get_config('local_cron_sql_queries', $queryname);
            if ($nextrun > time()) {
                $this->log("Skip processing file $queryname", 2);
                continue;
            }
            // Schedule the next query run.
            set_config($queryname, time() + $delay, 'local_cron_sql_queries');
            // Execute the SQL file.
            $this->execute_file($filepath, $queryname);
        }
        $this->log_finish("Done processing folder $folder", 1);
    }

    /**
     * Execute SQL file.
     *
     * @param string $filepath Path to SQL file.
     * @param string $queryname Query name.
     * @return void
     */
    protected function execute_file($filepath, $queryname) {
        global $DB;
        try {
            $this->log("Processing file $queryname", 2);
            // Split SQL file into separate queries.
            $sqls = explode(';', file_get_contents($filepath));
            // Execute each query.
            foreach($sqls as $sql) {
                $sql = $this->prepare_sql($sql);
                // Skip empty queries.
                if ($sql) {
                    $DB->execute($sql);
                }
            }
            $this->log("Done processing file $queryname", 2);
        } catch (\Exception $e) {
            $this->log("Error processing file $queryname: " . $e->getMessage(), 2);
        }
    }

    /**
     * Prepare SQL query.
     *
     * @param string $sql SQL query.
     * @return string Prepared SQL query.
     */
    protected function prepare_sql($sql) {
        global $CFG;

        // this is for compatibility with configurable_reports plugin.
        $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

        // these tokens are similar to configurable_reports,
        // but we don't have the same context here.
        // we use 0 to avoid SQL error, but the query will not work as expected.
        $sql = str_replace(['%%USERID%%', '%%COURSEID%%', '%%CATEGORYID%%'], 0, $sql);

        // See http://en.wikipedia.org/wiki/Year_2038_problem.
        $sql = str_replace(['%%STARTTIME%%', '%%ENDTIME%%'], ['0', '2145938400'], $sql);
        $sql = str_replace('%%WWWROOT%%', $CFG->wwwroot, $sql);
        $sql = preg_replace('/%{2}[^%]+%{2}/i', '', $sql);

        $sql = trim($sql);

        return $sql;
    }

}
