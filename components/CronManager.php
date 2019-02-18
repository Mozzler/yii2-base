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

        $stats = ['Entries' => 0, 'Entries Run' => 0, 'Entries Skipped' => 0, 'Entries Already Running' => 0, 'Errors' => 0, 'Tasks Run' => []];

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
        $stats['Entries'] = count($this->entries);
        /** @var TaskManager $taskManager */
        $taskManager = \Yii::$app->taskManager; // Need to trigger running a task using this.

        foreach ($this->entries as $cronEntryName => $cronEntry) {

            if (empty($cronEntry) || (!isset($cronEntry['class']) && !isset($cronEntry['scriptClass']))) {
                $stats['Errors']++;
                \Yii::error("The cronEntry is empty or invalid, can't process: " . var_export($cronEntry, true));
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
                \Yii::error("The cronObject is empty, there was an issue instanciating the object using the cronEntry: " . var_export($cronEntry, true));
                $stats['Errors']++;
                continue;
            }


            if (!$cronObject->shouldRunCronAtTime()) {
                // Skip
                \Yii::info("Skipping the running of the Cron as it shouldn't be run at this time. Cron: " . var_export($cronObject, true));
                $stats['Entries Skipped']++;
            }

            // Create (and auto-save) the task
            $task = $this->createTaskFromCronEntryObject($cronObject);

            // -- Check there isn't already an existing task for this cron entry:
            $taskModel = \Yii::createObject(Task::class);
            $existingTask = $taskModel->findOne([
                'scriptClass' => $task->scriptClass,
                'timeoutSeconds' => $task->timeoutSeconds,
                'name' => $task->name, // This is the specifically unique field which could be all we need to check on
                'triggerType' => $task->triggerType]);

            if (!empty($existingTask)) {
                \Yii::error("There's already an existing task for {$task->name} so skipping it. This likely means there's another CronManager running on another server.");
                $stats['Entries Already Running']++;
                continue;
            }

            $task->save(true, null, false); // Save without checking user permissions
            // Use the Task Manager to Run the task immediately
            $taskManager->run($task, true); //The important bit. Actually run the task! Without this it all is for naught

            $stats['Entries Run']++;
            \Yii::info("Started running the cron task: {$task->name}");
            $stats['Tasks Run'][] = "{$task->name} - TaskId: {$task->_id}";
        }

        $gcRan = self::gc();
        $stats['Garbage Collection Ran'] = json_encode($gcRan);

        \Yii::info("The Cron Manager run stats are: " . print_r($stats, true));
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
        return $task;
    }

}
