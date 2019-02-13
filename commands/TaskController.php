<?php

namespace mozzler\base\commands;

use mozzler\base\models\Task;
use mozzler\base\scripts\ScriptBase;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\ExitCode;

/**
 * This is used to run a specific task from the command line. Allowing for multiple processes to be run in parallel
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


    /**
     * This command runs the specified task.
     *
     * Usually triggered by the cronManager
     *
     * Expected the MongoDB objectId of the task to be run.
     *
     * @throws \yii\base\InvalidConfigException
     * @param $taskId string - The MongoDB Id of the task to run
     * @return int Exit code
     */
    public function actionRun($taskId)
    {
        if (empty($taskId)) {
            $this->stderr("#### Error ####\nNo or invalid taskId provided", Console::FG_RED, Console::UNDERLINE);
        }

        // Check the MongoDB entry
        // Get the timeoutSeconds and set the local timeout to that
        /** @var Task $taskModel */
        $taskModel = \Yii::createObject(Task::class);
        /** @var Task $task */
        $task = $taskModel->findOne($taskId);
        if (empty($task)) {
            $this->stderr("#### Error ####\nCouldn't find a Task with the taskId of " . json_encode($taskId), Console::FG_RED, Console::BOLD);
        }
        set_time_limit($task->timeoutSeconds);

        $task->status = $task::STATUS_INPROGRESS;
        $task->save(true, null, false);  // Save without checking permissions


        // ---------------------------------
        //   Run the Script
        // ---------------------------------
        $this->stdout(
            "Running Task: {$task->scriptClass}\n"
            . "Timeout: {$task->timeoutSeconds} seconds\n"
            . "Trigger Type: {$task->triggerType}\n"
            . "Status: {$task->status}\n"
            . "Config: " . json_encode($task->config) . "\n"
        );
        /** @var ScriptBase $script */
        $script = \Yii::createObject(ArrayHelper::merge($task->config, ['class' => $task->scriptClass()]));
        $script->run($task); // Actually run the script (task)


        // -- Unless the script set the status to error, then save this as complete
        if ($task->status !== Task::STATUS_ERROR) {
            $task->status = Task::STATUS_COMPLETE;
        } else {
            $this->stderr("#### Task Errored ####\n", Console::FG_RED, Console::BOLD);
        }
        $saved = $task->save(true, null, false); // Save without checking permissions

        // -- Output the Log (if requested)
        if ($this->outputLog) {
            $this->stdout($task->returnLogLines());
        }
        $this->stdout("Task Processing Completed");

        return true === $saved ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
