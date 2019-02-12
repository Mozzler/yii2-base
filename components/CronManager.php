<?php
namespace mozzler\base\components;

/**
 * To use the cron manager, add it to the `web.php` components:
 * 
 * ```
 * 'components' => [
 *      'cronManager' => [
 *          'class' => 'mozzler\base\components\CronManager',
 *          'entries' => [
 *              'backgroundTasks' => [
 *                  'scriptClass' => 'mozzler\base\cron\BackgroundTask',
 *                  'config' => [],
 *                  'minutes' => '0,30',
 *                  'hours' => '*'
 *                  'dayMonth' => '*'
 *                  'dayWeek' => '*',
 *                  'timezone' => 'Australia/Adelaide',
 *                  'active' => true
 *              ]
 *          ]
 *      ]
 * ]
 * ```
 */


class CronManager extends yii\base\Component
{

    public static $gcPercent = 1;

    public static $gcAgeDays = 30;

    public $entries = [];

    public function run()
    {
        // see code below

        self::gc();
    }

    protected static function gc()
    {
        // 1% of the time delete all records that are older than 30 days
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