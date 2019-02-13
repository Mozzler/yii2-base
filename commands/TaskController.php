<?php

namespace mozzler\base\commands;

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

        // -- Run the Script

        // @todo: Instanciate the task

        return ExitCode::OK;
    }
}
