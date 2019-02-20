<?php

namespace mozzler\base\components;

use mozzler\base\cron\CronEntry;
use mozzler\base\models\Task;
use \yii\helpers\ArrayHelper;
use \yii\base\Component;

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
class CronManager extends Component
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

    /**
     * @var array
     * Any base entries which should be scheduled automatically.
     * The backgroundTasks is set to run every minute to process any tasks which have been created
     * but are scheduled for later. Most likely using \Yii::$app->taskManager->schedule or simply creating a Task set to TRIGGER_TYPE_BACKGROUND
     * E.g If an admin user requests a large CSV report get produced and emailed to them you would schedule that in the background.
     *
     * Where as a report that's sent at midnight every night would be scheduled using a cron entry in the config/console.php file
     */
    public $defaultEntries = [
        'backgroundTasks' => [
            'class' => 'mozzler\base\cron\BackgroundTasksCronEntry',
            'config' => [],
            'minutes' => '*',
            'hours' => '*',
            'dayMonth' => '*',
            'dayWeek' => '*',
            'timezone' => 'Australia/Adelaide',
            'active' => true,
        ]];

    public function run()
    {
        $cronRun = $this->generateCronRun();
        
        // Cron has already been run for this inverval, so do nothing
        if (!$cronRun) {
            return;
        }

        $stats = [
            'Entries' => 0,
            'Entries Run' => 0,
            'Entries Skipped' => 0,
            'Entries Already Running' => 0,
            'Errors' => 0
        ]

        $this->entries = ArrayHelper::merge($this->defaultEntries, $this->entries);
        $stats['Entries'] = count($this->entries);
        /** @var TaskManager $taskManager */
        $taskManager = \Yii::$app->taskManager; // Need to trigger running a task using this.

        foreach ($this->entries as $cronEntryName => $cronEntry) {

            if (empty($cronEntry) || (!isset($cronEntry['class']) && !isset($cronEntry['scriptClass']))) {
                $stats['Errors']++;
                $cronRun->addLog("The cronEntry is empty or invalid, can't process: " . var_export($cronEntry, true),'error');
            }

            /** @var CronEntry $cronObject */
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
                $cronRun->addLog("The cronObject is empty, there was an issue instanciating the object using the cronEntry: " . var_export($cronEntry, true),'error');
                $stats['Errors']++;
                continue;
            }

            if ($cronEntry->shouldRunCronAtTime()) {
                $task = \Yii::$app->taskManager->schedule($cronEntry->scriptClass, $cronEntry->config, $cronEntry->timeoutSeconds, true);

                $stats['Entries Run']++;
                $cronRun->addLog("Script scheduled ({$cronEntry->scriptClass}) with taskId: {$task->id}", 'info');
            } else {
                $stats['Entries Skipped']++;
            }
        }

        // TODO: look at gc into a Trait

        $gcRan = self::gc();
        $stats['Garbage Collection Ran'] = json_encode($gcRan);

        $cronRun->stats = $stats;
        $cronRun->status = 'complete';
        if (!$cronRun->save()) {
            $cronRun->addLog('TODO: Shit!!', 'error');
        }

        return $stats;
    }

    /**
     * Garbage Collection
     *
     * @param bool $force
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * Deletes all Task records that are older than self::$gcAgeDays
     */
    public static function gc($force = false)
    {
        if ($force || mt_rand(0, 1000000) < self::$gcProbability) {
            // 1% of the time delete all Task records that are older than 30 days

            /** @var Task $taskModel */
            $taskModel = \Yii::createObject(Task::class);
            $unixTimeOfGC = time() - (self::$gcAgeDays * 86400);
            \Yii::$app->rbac->ignoreCollection(Task::collectionName()); // Get around RBAC issues
            $taskModel->deleteAll(['<', 'createdAt', $unixTimeOfGC]);
            \Yii::$app->rbac->dontIgnoreCollection(Task::collectionName());
            return true;
        }
        return false;
    }

    /**
     * @param $cronEntryObject CronEntry
     * @return |null
     * @throws \yii\base\InvalidConfigException
     */
    protected function createTaskFromCronEntryObject($cronEntryObject)
    {
/*
        if (empty($cronEntryObject)) {
            return null;
        }
        $unixTimestampMinuteStarted = round(floor(time() / 60) * 60); // When this minute started - Used for identifying specific tasks
        $taskConfig =
            [
                'config' => $cronEntryObject->config,
                'scriptClass' => $cronEntryObject->scriptClass,
                'timeoutSeconds' => $cronEntryObject->timeoutSeconds,
                'status' => Task::STATUS_PENDING,
                'name' => "{$unixTimestampMinuteStarted}-Cron-{$cronEntryObject->scriptClass}", // It's important that this be complex enough that we can detect other tasks aren't already running with the same instance
                'triggerType' => Task::TRIGGER_TYPE_INSTANT
            ];
        /** @var Task $task */
        $task = \Yii::createObject(Task::class);
        $task->load($taskConfig, '');
        return $task;*/
    }

    protected function createCronRun() {
        $nearestMinuteTimestamp = round(floor($utcUnixTimestamp / 60) * 60);

        $cronRun = \Yii::createObject('mozzler\base\models\CronRun');
        $cronRun->load([
            'timestamp' => $nearestMinuteTimestamp
            'stats' => []
        ],"");

        if (!$cronRun->save()) {
            // Unable to save cron due to an entry already existing for this
            // timestamp minute interval
            return;
        }
    }

}


ar cronRun = r.createModel("rappsio.application.cronrun", {
    "timestamp": timestampMinute,
    "summary": "Running cron commenced"
})

// Log that cron is being run for this timestamp
if (!cronRun.save()) {
    r.log("trace", "Cron has already been executed for this time interval ("+timestampMinute+")");
    return false;
}