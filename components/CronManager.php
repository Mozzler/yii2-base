<?php

namespace mozzler\base\components;

use mozzler\base\cron\CronEntry;
use mozzler\base\models\Task;
use \yii\helpers\ArrayHelper;

/**
 * To use the cron manager, add it to the `web.php` components:
 *
 * ```
 * 'components' => [
 *      'cronManager' => [
 *          'class' => 'mozzler\base\components\CronManager',
 *          'entries' => [
 *              'backgroundTasks' => [
 *                  'class' => 'mozzler\base\cron\BackgroundTask',
 *                  'config' => [],
 *                  'minutes' => '*',
 *                  'hours' => '*'
 *                  'dayMonth' => '*'
 *                  'dayWeek' => '*',
 *                  'timezone' => 'Australia/Adelaide',
 *                  'active' => true,
 *
 *              ]
 *          ]
 *      ]
 * ]
 * ```
 */
class CronManager extends yii\base\Component
{

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when running the cron.
     * Defaults to 10000, meaning 1% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public static $gcProbability = 10000;

    public static $gcAgeDays = 30;

    public $entries = [];


    public function run()
    {

        $defaultEntries = ['backgroundTasks' => [
            'class' => 'mozzler\base\cron\BackgroundTasksCronEntry',
            'config' => [],
            'minutes' => '*',
            'hours' => '*',
            'dayMonth' => '*',
            'dayWeek' => '*',
            'timezone' => 'Australia/Adelaide',
            'active' => true,

        ]
        ];

        $this->entries = ArrayHelper::merge($defaultEntries, $this->entries);

        foreach ($this->entries as $cronEntryName => $cronEntry) {

            if (empty($cronEntry) || (!isset($cronEntry['class']) && !isset($cronEntry['scriptClass']))) {
                // @todo: Error
            }

            $cronObject = null;
            if (!empty($cronEntry['class'])) {
                // Grab the defaults from the class, but override them with the current

                $cronObject = \Yii::createObject($cronEntry);

            } else if (!empty($cronEntry['scriptClass'])) {
                // -- Creating a new object based on the generic class... Using the provided info
                $cronEntry['class'] = CronEntry::class;
                $cronObject = \Yii::createObject($cronEntry);
            }

            if (empty($cronObject)) {
                // @todo: Error
                continue;
            }
            // Create (and auto-save) the task
            $task = $this->createTaskFromCronEntryObject($cronObject);

            // Use the Task Manager to Run the task immediately


        }


        // Process the Entries Array
        // Instanciate the CronEntries
        //

        self::gc();
    }

    protected static function gc($force = false)
    {
        if ($force || mt_rand(0, 1000000) < self::$gcProbability) {
            // 1% of the time delete all Task records that are older than 30 days

            // @todo: delete all Task records that are older than self::$gcAgeDays

        }
    }

    /**
     * @param $cronEntryObject CronEntry
     * @return |null
     * @throws \yii\base\InvalidConfigException
     */
    protected function createTaskFromCronEntryObject($cronEntryObject)
    {

        if (empty($cronEntryObject)) {
            return null;
        }

        $taskConfig =
            [
                'class' => Task::class,
                'config' => $cronEntryObject->config,
                'scriptClass' => $cronEntryObject->scriptClass,
                'timeoutSeconds' => $cronEntryObject->timeoutSeconds,
                'status' => Task::STATUS_PENDING,
                'triggerType' => Task::TRIGGER_TYPE_INSTANT
            ];
        $task = \Yii::createObject($taskConfig);
        $task->save(true, null, false); // Save without checking permissions
        return $task;
    }

}

/*
 *  Rappsio code
 * 
 * library.run = function(cronJobs) {
    // current timestamp
    var timestamp = Math.floor(Date.now() / 1000);
    
    // timestamp to the nearest minute
    var timestampMinute = parseInt(timestamp/60)*60;
    
    // TODO: Make sure cron hasn't already been run for this timestamp
    var cronRun = r.createModel("rappsio.application.cronrun", {
        "timestamp": timestampMinute,
        "summary": "Running cron commenced"
    })
    
    // Log that cron is being run for this timestamp
    if (!cronRun.save()) {
        r.log("trace", "Cron has already been executed for this time interval ("+timestampMinute+")");
        return false;
    }
    
    var sortedScripts = this.sortScripts(cronJobs, timestampMinute);
    var cronMessage = "Executed: " + (sortedScripts.execute.length == 0 ? "None " : "");
    
    // execute scripts as admin background tasks
    for (var s in sortedScripts.execute) {
        var script = sortedScripts.execute[s];
        // Task Manager
        r.createTask(script.script, script.config, true);
        cronMessage += script.name+" ("+script.script+") ";
    }
    
    cronMessage += "\nSkipped: " + (sortedScripts.skip.length == 0 ? "None" : "");
    for (var s in sortedScripts.skip) {
        var script = sortedScripts.skip[s][0];
        var reason = sortedScripts.skip[s][1];
        cronMessage += script.name+" ("+script.script+") due to "+reason+".\n";
    }
    
    // Log that cron has been completed for this timestamp
    // Include names of executed scripts and names of skipped scripts
    cronRun.summary = cronMessage;
    cronRun.save();
    
    return cronRun;
}

/*
 * Get an array of all the scripts that should be executed
 * 
 * Cron entry should be in the format:
 * {
        "name": "",
        "script": "",
        "config": {},
        "minutes": "",
        "hour": "",
        "day_month": "",
        "month": "",
        "day_week": "",
        "timezone": "Australia/Adelaide"
 * }
 *
library.sortScripts = function(cronEntries, timestamp) {
    var scriptsExecute = [];
    var scriptsSkipped = [];
    for (var c in cronEntries) {
        var cron = cronEntries[c];
        var cronName = c;
        var timezone = r.php().instantiate("DateTimezone", [cron.timezone]);
        var phpDate = r.php().DateTime(null, timezone);
        phpDate.setTimestamp(timestamp);
        
        if (cron.active !== true) {
            continue;
        }
        
        // Test interval matches for all intervals
        var match = "ok";
        if (!this.intervalMatch(cron.minutes, phpDate, "i")) match = "minute";
        if (!this.intervalMatch(cron.hour, phpDate, "h")) match = "hour";
        if (!this.intervalMatch(cron["day_month"], phpDate, "d")) match = "day_month";
        if (!this.intervalMatch(cron.month, phpDate, "m")) match = "month";
        if (!this.intervalMatch(cron["day_week"], phpDate, "w")) match = "day_week";
        
        cron.name = cronName;
        
        if (match == "ok") {
            scriptsExecute.push(cron);
        } else {
            r.log("trace", "Skipped as match failed: "+match);
            scriptsSkipped.push([cron, match]);
        }
    }
    
    return {
        "execute": scriptsExecute,
        "skip": scriptsSkipped
    }
}

library.intervalMatch = function(intervals, phpDate, intervalFormat) {
    if (intervals == "*") {
        return true;
    }
    
    // If we have a comma separated string, split into array
    if (typeof(intervals) == "string") {
        if (intervals.match(",")) {
            intervals = intervals.split(",");
        }
    }
    
    // Ensure we have an array
    if (!Array.isArray(intervals)) {
        intervals = [intervals];
    }
    
    // force integers
    for (var i in intervals) {
        intervals[i] = parseInt(intervals[i]);
    }

    var currentInterval = parseInt(phpDate.format(intervalFormat));

    // return true if the interval is found
    return (intervals.indexOf(currentInterval) > -1);
}
 * 
 */