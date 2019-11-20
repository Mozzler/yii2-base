<?php

namespace mozzler\base\components;

use mozzler\base\cron\CronEntry;
use mozzler\base\models\CronRun;
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
            'threadName' => '',
        ]];

    public function run()
    {
        /** @var CronRun $cronRun */
        $cronRun = $this->createCronRun();

        // Cron has already been run for this interval, so do nothing
        if (!$cronRun) {
            return "Cron has already been run this minute, wait ". ( 60 - date('s') ) . "s to run it again";
        }

        $stats = [
            'Entries' => 0,
            'Entries Run' => 0,
            'Entries Skipped' => 0,
            'Tasks Run' => [],
            'Errors' => 0
        ];

        $this->entries = ArrayHelper::merge($this->defaultEntries, $this->entries);
        $stats['Entries'] = count($this->entries);
        /** @var TaskManager $taskManager */
        $taskManager = \Yii::$app->taskManager; // Need to trigger running a task using this.

        foreach ($this->entries as $cronEntryName => $cronEntry) {

            if (empty($cronEntry) || (!isset($cronEntry['class']) && !isset($cronEntry['scriptClass']))) {
                $stats['Errors']++;
                $cronRun->addLog("The cronEntry is empty or invalid, can't process: " . var_export($cronEntry, true), 'error');
            }

            // -- Create the Cron Entry
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
                $cronRun->addLog("The cronObject is empty, there was an issue instanciating the object using the cronEntry: " . var_export($cronEntry, true), 'error');
                $stats['Errors']++;
                continue;
            }

            if ($cronObject->shouldRunCronAtTime()) {
                try {
                    $task = $taskManager->schedule($cronObject->scriptClass, $cronObject->config, $cronObject->timeoutSeconds, true, $cronObject->threadName);
                } catch (\Throwable $exception) {
                    $stats['Error'] = Tools::returnExceptionAsString($exception);
                    $stats['Errors']++;
                }

                $stats['Entries Run']++;
                $cronRun->addLog("Script scheduled ({$cronObject->scriptClass}) with taskId: {$task->id}", 'info');
                $stats['Tasks Run'][] = "{$task->name} - TaskId: {$task->getId()}";
            } else {
                $stats['Entries Skipped']++;
            }
            $cronRun->save();
        }

        $cronRun->stats = $stats;
        $cronRun->status = 'complete';
        if (!$cronRun->save()) {
            $error = "Unable to save the cronRun. Error: " . json_encode($cronRun->getErrors());
            \Yii::error($error);
            $cronRun->addLog($error, 'error');
            $stats['Error'] = $error;
            $stats['Errors']++;
        }
        return $stats;
    }

    /**
     * @return CronRun|boolean
     * @throws \yii\base\InvalidConfigException
     */
    protected function createCronRun()
    {
        $nearestMinuteTimestamp = round(floor(time() / 60) * 60);

        /** @var CronRun $cronRun */
        $cronRun = \Yii::createObject('mozzler\base\models\CronRun');
        $cronRun->load([
            'timestamp' => $nearestMinuteTimestamp,
            'stats' => [],
        ], "");

        // @todo: try/catch this and gracefully deal with the Exception 'yii\mongodb\Exception' example message: 'E11000 duplicate key error collection: viterra.app.cronRun index: timestampUniqueId dup key: { : 1550638500 }'
        if (!$cronRun->save()) {
            // Unable to save cron due to an entry already existing for this
            // timestamp minute interval
            return false;
        }
        return $cronRun;
    }

}