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
class TaskController extends BaseController
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

        $task = \Yii::$app->taskManager->runTask($task);

        if ($task->hasErrors()) {
            $this->stderr("#### Error ####\nCouldn't save the taskId of " . json_encode($taskId) . "\n" . json_encode($task->getErrors()), Console::FG_RED, Console::BOLD);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        else {
            $this->stdout("Task Processing Completed\n");
            return Task::STATUS_ERROR === $task->status ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
        }
        
    }
}
