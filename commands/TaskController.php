<?php

namespace mozzler\base\commands;

use mozzler\base\models\Task;
use mozzler\base\scripts\ScriptBase;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

/**
 * This class offers easy way to implement `config` collection.
 */
class TaskController extends Controller
{
    /**
     * This command runs the specified task.
     *
     * Usually triggered by the cronManager
     *
     * Expected the MongoDB objectId of the task to be run.
     *
     * @return int Exit code
     */
    public function actionRun($taskId)
    {
        \Yii::$app->taskManager->run();


        // Check the MongoDB entry
        // Get the timeoutSeconds and set the local timeout to that
        /** @var Task $taskModel */
        $taskModel = \Yii::createObject(Task::class);
        /** @var Task $task */
        $task = $taskModel->findOne($taskId);
        set_time_limit($task->timeoutSeconds);

        $task->status = $task::STATUS_INPROGRESS;
        $task->save(true, null, false);  // Save without checking permissions


        // -- Run the Script
        /** @var ScriptBase $script */
        $script = new $task->scriptClass();
        $script->run($task); // Actually run the script (task)

        // -- Unless the script set the status to error, then save this as complete
        if ($task->status !== Task::STATUS_ERROR) {
            $task->status = Task::STATUS_COMPLETE;
        }
        $saved = $task->save(true, null, false); // Save without checking permissions

        return true === $saved ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
