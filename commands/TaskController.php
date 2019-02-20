<?php

namespace mozzler\base\commands;

use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
use mozzler\base\models\Task;
use mozzler\base\scripts\ScriptBase;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;

/**
 * This is the task manager which works in concert with the cron manager.
 *
 * This is used to run a specific task from the command line.
 * Allowing for multiple processes to be run in parallel if triggered asynchronously.
 *
 */
class TaskController extends Controller
{

    public $outputLog = true;

    public function options($actionID)
    {
        return ['outputLog'];
    }

    public function optionAliases()
    {
        return ['o' => 'outputLog'];
    }

    public function actionRedo($taskId)
    {

        if (empty($taskId)) {
            $this->stderr("#### Error ####\nNo or invalid taskId provided", Console::FG_RED, Console::UNDERLINE);
            return ExitCode::USAGE;
        }

        // Check the MongoDB entry
        // Get the timeoutSeconds and set the local timeout to that
        /** @var Task $taskModel */
        $taskModel = \Yii::createObject(Task::class);
        /** @var Task $task */
        $task = $taskModel->findOne(['_id' => new ObjectId($taskId)]);
        if (empty($task)) {
            $this->stderr("#### Error ####\nCouldn't find a Task with the taskId of " . json_encode($taskId) . "\n", Console::FG_RED, Console::BOLD);
            return ExitCode::USAGE;
        }

        $task->status = Task::STATUS_PENDING;
        $task->save(true, null, false);

        return $this->actionRun($taskId);
    }

    public function actionView($taskId)
    {

        if (empty($taskId)) {
            $this->stderr("#### Error ####\nNo or invalid taskId provided", Console::FG_RED, Console::UNDERLINE);
            return ExitCode::USAGE;
        }

        // Check the MongoDB entry
        // Get the timeoutSeconds and set the local timeout to that
        /** @var Task $taskModel */
        $taskModel = \Yii::createObject(Task::class);
        /** @var Task $task */
        $task = $taskModel->findOne(['_id' => new ObjectId($taskId)]);
        if (empty($task)) {
            $this->stderr("#### Error ####\nCouldn't find a Task with the taskId of " . json_encode($taskId) . "\n", Console::FG_RED, Console::BOLD);
            return ExitCode::USAGE;
        }

        $this->stdout(
            "Task Name: {$task->name}\n"
            . "Script: {$task->scriptClass}\n"
            . "Timeout: {$task->timeoutSeconds} seconds\n"
            . "Trigger Type: {$task->triggerType}\n"
            . "Status: {$task->status}\n"
            . "Config: " . json_encode($task->config) . "\n"
            . "\n"
        );
        $this->stdout("=======================================\n==   Log Entries\n=======================================\n{$task->returnLogLines()}\n");
        return ExitCode::OK;
    }


    /**
     * This command runs the specified task. Usually called by cron/run. Needs the TaskId
     *
     * Usually triggered by the cronManager
     *
     * Expects the MongoDB objectId of the task to be run.
     *
     * @throws \yii\base\InvalidConfigException
     * @param $taskId string - The MongoDB Id of the task to run
     * @return int Exit code
     */
    public function actionRun($taskId)
    {
        if (empty($taskId)) {
            $this->stderr("#### Error ####\nNo or invalid taskId provided", Console::FG_RED, Console::UNDERLINE);
            return ExitCode::USAGE;
        }

        // Check the MongoDB entry
        // Get the timeoutSeconds and set the local timeout to that
        /** @var Task $taskModel */
        $taskModel = \Yii::createObject(Task::class);
        /** @var Task $task */
        $task = $taskModel->findOne(['_id' => new ObjectId($taskId)]);
        if (empty($task)) {
            $this->stderr("#### Error ####\nCouldn't find a Task with the taskId of " . json_encode($taskId) . "\n", Console::FG_RED, Console::BOLD);
            return ExitCode::USAGE;
        }
        set_time_limit($task->timeoutSeconds);


        if (Task::STATUS_PENDING !== $task->status) {
            $this->stderr("## Error: Task isn't in pending status. Can't run it.\nTask Id: {$task->_id}\nTask Name: {$task->name}\nTask State: {$task->status}\n", Console::FG_RED, Console::BOLD);
            return ExitCode::USAGE;
        }

        $task->status = $task::STATUS_INPROGRESS;
        $task->save(true, null, false);  // Save without checking user permissions


        // ---------------------------------
        //   Run the Script
        // ---------------------------------
        $this->stdout(
            "Running Task: {$task->scriptClass}\n"
            . "Timeout: {$task->timeoutSeconds} seconds\n"
            . "Trigger Type: {$task->triggerType}\n"
            . "Status: {$task->status}\n"
            . "Config: " . json_encode($task->config) . "\n"
            . "\n"
        );

        try {

            /** @var ScriptBase $script */
            $script = \Yii::createObject(ArrayHelper::merge($task->config, ['class' => $task->scriptClass]));
            $scriptReturn = $script->run($task); // Actually run the script (task)
        } catch (\Throwable $exception) {
            $this->stderr(Tools::returnExceptionAsString($exception), Console::FG_RED, Console::BOLD);
            $task->status = Task::STATUS_ERROR;
//            $task->save(true, null, false);
//            $this->stdout("✘ Task Errored");
//            return ExitCode::SOFTWARE;
        }


        // -- Check the results
        $taskWithoutLogs = $task->toArray();
        unset($taskWithoutLogs['logs']);
        $this->stdout("The task is:\n" . print_r($taskWithoutLogs, true));
        if (!empty($scriptReturn)) {
            $task->addLog("Script returned\n-----------------------\n" . var_export($scriptReturn, true));
        }

        // -- Unless the script set the status to error, then save this as complete
        if ($task->status !== Task::STATUS_ERROR) {
            $task->status = Task::STATUS_COMPLETE;
        } else {
            $this->stderr("#### Task Errored ####\nTask Id: {$task->_id}\nTask Name: {$task->name}\n", Console::FG_RED, Console::BOLD);
        }
        $saved = $task->save(true, null, false); // Save without checking permissions

        // -- Output the Log (if requested)
        if ($this->outputLog) {
            $this->stdout("\n\n=======================================\n==   Log Entries\n=======================================\n{$task->returnLogLines()}\n");
        }

        // -- Done
        $this->stdout("Task Processing Completed\n");
        return Task::STATUS_ERROR === $task->status ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
