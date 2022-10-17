<?php

namespace mozzler\base\components;

use mozzler\base\cron\CronEntry;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\CronRun;
use mozzler\base\models\Task;
use \yii\helpers\ArrayHelper;
use \yii\base\Component;
use yii\helpers\VarDumper;

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
            'active' => false, // We aren't actually using this, it doesn't do anything it's just wasting DB space, so should have it disabled by default
            'threadName' => '',
        ]
    ];

    public function run()
    {
        /** @var CronRun $cronRun */
        $cronRun = $this->createCronRun();

        // Cron has already been run for this interval, so do nothing
        if (!$cronRun) {
            return "Cron has already been run this minute, wait " . (60 - date('s')) . "s to run it again\n";
        }

        $stats = [
            'Entries' => 0,
            'Entries Run' => 0,
            'Entries Skipped' => 0,
            'Tasks Run' => [],
            'Errors' => 0,
            'Error Messages' => [],
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
                $cronRun->addLog("The cronObject is empty, there was an issue instantiating the object using the cronEntry: " . var_export($cronEntry, true), 'error');
                $stats['Errors']++;
                continue;
            }

            if ($cronObject->shouldRunCronAtTime()) {
                try {
                    $task = $taskManager->schedule($cronObject->scriptClass, $cronObject->config, $cronObject->timeoutSeconds, true, $cronObject->threadName);
                    $stats['Entries Run']++;
                    $cronRun->addLog("Script scheduled ({$cronObject->scriptClass}) with taskId: {$task->id}", 'info');
                    $stats['Tasks Run'][] = "{$task->name} - TaskId: {$task->getId()}";
                } catch (\Throwable $exception) {
                    $errorMessage = "Crun/run Exception whilst scheduling a Task: " . \Yii::$app->t::returnExceptionAsString($exception) . "\n----\n" . VarDumper::export(['cronEntry' => ArrayHelper::toArray($cronEntry)]);
                    \Yii::error($errorMessage);
                    $stats['Error Messages'][] = $errorMessage;
                    $stats['Errors']++;
                }


            } else {
                $stats['Entries Skipped']++;
            }
            $this->saveAndIgnoreAlreadyRunError($cronRun);
        }

        $cronRun->stats = $stats;
        $cronRun->status = 'complete';
        if (!$cronRun->save(true, null, false)) {
            $error = "Unable to save the cronRun. Error: " . VarDumper::export($cronRun->getErrors());
            \Yii::error(new BaseException("Unable to save the {$cronRun->ident()}", 500, null, [
                'errors' => $cronRun->getErrors(),
                'cronRun' => $cronRun->toArray(),
            ]));
            $cronRun->addLog($error, 'error');
            $stats['Error'] = $error;
            $stats['Errors']++;
        }
        return $stats;
    }


    /**
     * @param $errors
     *
     * This is because we don't want the System Log errors around Cron having already been run
     *
     * Error saving CronRun . Validation Error(s): [
     * 'timestampUniqueId' => [
     * [
     * 'Cron has already been run for this timetamp',
     * ],
     * ],
     * ]
     */
    public function ignoreAlreadyRunError($errors)
    {
        if (empty($errors)) {
            return $errors;
        }

        if (!is_array($errors)) {
            return $errors;
        }

        if (count($errors) > 1) {
            // There's more than a single error
            return $errors;
        }

        $timestampUniqueIdError = ArrayHelper::getValue($errors, 'timestampUniqueId.0.0');
        if (empty($timestampUniqueIdError)) {
            return $errors;
        }
        if ('Cron has already been run for this timestamp' === $timestampUniqueIdError) {
            // Note: The duplicate message Used to be incorrectly have 'timetamp' but is now 'timestamp'
            // If there's issues with this changing then you could check for whateve rthe yii2-base models/CronRun.php:modelIndexes().timestampUniqueId.duplicateMessage is
            // or just accept that any timestampUniqueId entry can be ignored
            return []; // Ignore this error
        }

        return $errors;
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

        $saved = $this->saveAndIgnoreAlreadyRunError($cronRun);
        return false === $saved ? false : $cronRun; // If false then return false, otherwise return the $cronRun
    }


    public function saveAndIgnoreAlreadyRunError($cronRun)
    {
        if (!$cronRun->save(true, null, false)) {

            // Ignore errors around unable to save cron due to an entry already existing for this timestamp minute interval
            if (empty($this->ignoreAlreadyRunError($cronRun->getErrors()))) {
                return false;
            }
            \Yii::error(new BaseException("Error saving {$cronRun->ident()}. Validation Error(s)", 500, null, ['Errors' => $cronRun->getErrors(), CronRun::class => $cronRun->toArray()])); // A nicer Exception
            return false;
        }
        return true;
    }

}
