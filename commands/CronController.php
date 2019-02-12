<?php

namespace mozzler\base\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;
 
/**
 * This class offers easy way to implement `config` collection.
 */
class CronController extends Controller
{
    /**
     * This command runs cron for a given minute interval
     *
     * @return int Exit code
     */
    public function actionRun()
    {
        \Yii::$app->cronManager->run();

        return ExitCode::OK;
    }
}
