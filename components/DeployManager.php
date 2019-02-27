<?php

namespace mozzler\base\components;

use mozzler\base\models\Task;
use yii\base\Exception;
use \yii\helpers\ArrayHelper;
use \yii\base\Component;

/**
 * To use the Deploy manager, add it to the `console.php` or `common.php` with the required config.
 *
 * e.g
 *
 * ```
 * [
 * "versionParam" => "config.apiVersionId",
 * "init" => [
 * "indexes" => [
 * "command" => "deploy/sync",
 * "params" => []
 * ],
 * "adminUser" => [
 * "command" => "auth/init-credentials",
 * "params" => []
 * ],
 * "config" => [
 * "command" => "config/init",
 * "params" => []
 * ]
 * ],
 * "redeploy" => [
 * "indexes" => [
 * "command" => "deploy/sync",
 * "params" => []
 * ],
 * "config" => [
 * "command" => "config/init",
 * "params" => []
 * ],
 * "clearData" => [
 * "command" => "deploy/drop-collections",
 * "config" => ["app.favourites", "mozzler.auth.user"],
 * "version" => "0.1.1"
 * ]
 * ]
 * ]
 * ```
 */
class DeployManager extends Component
{
    public $init = [];
    public $redeploy = [];
    public $versionParam = "apiVersionNumber"; // e.g \Yii::$app->params['apiVersionNumber'] will be used for the version number


    /**
     * @param $command string e.g 'init' or 'redeploy', which should correspond with the appropriate configuration
     * @return array
     * @throws \yii\base\Exception
     *
     * e.g $command
     */
    public function run($command)
    {
        if (empty($command)) {
            throw new Exception("The DeployManager run() command expects 'init' or 'redeploy' as commands, none provided");
        }

        $config = $this->$command;
        $currentVersion = \Yii::$app->params[$this->versionParam];

        $stats = [
            'Command' => $command,
            'TimeRun' => time(),
            'TimeRun Human Readable' => date('r'),
            'Current Version' => 'v' . $currentVersion,

            'Entries' => count($config),
            'Entries Run' => 0,
            'Entries Skipped' => 0,
            'Scripts Run' => [],

            'Config' => $config,

            'Errors Count' => 0,
            'Errors' => [],
            'Log' => [],
        ];

        if (empty($config)) {
            $stats['Errors Count']++;
            $stats['Errors'][] = "Invalid Command or empty configuration, nothing to process";
            return $stats;
        }


        foreach ($config as $entryName => $entry) {

            // -- Validity check
            if (empty($entry) || (!isset($entry['command']) && !isset($entry['script']))) {
                $stats['Errors Count']++;
                $stats['Errors'][] = "Command: {$command} - Entry {$entryName} : The entry is empty, need a command or script. " . json_encode($entry);
            }

            // -- Version Check
            if (isset($entry['version']) && $entryName['version'] !== $currentVersion) {
                $stats['Entries Skipped']++;
                $stats['Log'][] = "Command: {$command} - Entry {$entryName} - Ignoring the entry as expected version: {$entryName['version']} !== current version: {$currentVersion}";
            }

            if (!empty($entry['command'])) {

                $params = isset($entry['params']) ? $entry['params'] : '';
                $stats['Log'][] = "Command: {$command} - Entry {$entryName} - "

            }

        }

//
//        $this->entries = ArrayHelper::merge($this->defaultEntries, $this->entries);
//        $stats['Entries'] = count($this->entries);
//        /** @var TaskManager $taskManager */
//        $taskManager = \Yii::$app->taskManager; // Need to trigger running a task using this.
//
//        foreach ($this->entries as $cronEntryName => $cronEntry) {
//
//            if (empty($cronEntry) || (!isset($cronEntry['class']) && !isset($cronEntry['scriptClass']))) {
//                $stats['Errors']++;
//                $cronRun->addLog("The cronEntry is empty or invalid, can't process: " . var_export($cronEntry, true), 'error');
//            }
//
//            // -- Create the Cron Entry
//            /** @var CronEntry $cronObject */
//            $cronObject = null;
//            if (!empty($cronEntry['class'])) {
//                // Grab the defaults from the class, but override them with the current
//                $cronObject = \Yii::createObject($cronEntry);
//
//            } else if (!empty($cronEntry['scriptClass'])) {
//                // -- Creating a new object based on the generic class... Using the provided info
//                $cronEntry['class'] = CronEntry::class;
//                $cronObject = \Yii::createObject($cronEntry);
//            }
//
//            if (empty($cronObject)) {
//                $cronRun->addLog("The cronObject is empty, there was an issue instanciating the object using the cronEntry: " . var_export($cronEntry, true), 'error');
//                $stats['Errors']++;
//                continue;
//            }
//
//            if ($cronObject->shouldRunCronAtTime()) {
//                $task = \Yii::$app->taskManager->schedule($cronObject->scriptClass, $cronObject->config, $cronObject->timeoutSeconds, true);
//
//                $stats['Entries Run']++;
//                $cronRun->addLog("Script scheduled ({$cronObject->scriptClass}) with taskId: {$task->id}", 'info');
//                $stats['Tasks Run'][] = "{$task->name} - TaskId: {$task->getId()}";
//            } else {
//                $stats['Entries Skipped']++;
//            }
//        }
//
//        // TODO: look at gc into a Trait
//
//        $gcRan = self::gc();
//        $stats['Garbage Collection Ran'] = json_encode($gcRan);
//
//        $cronRun->stats = $stats;
//        $cronRun->status = 'complete';
//        if (!$cronRun->save()) {
//            $cronRun->addLog('TODO: Shit!!', 'error');
//            $stats['Errors']++;
//        }

        return $stats;
    }

}