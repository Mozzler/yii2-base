<?php

namespace mozzler\base\commands;

use yii\console\ExitCode;

/**
 * This class offers easy way to implement `config` collection.
 */
class CronController extends BaseController
{
    /**
     * This command runs cron for a given minute interval
     *
     * @return int Exit code
     */
    public function actionRun()
    {
        $this->stdout("Cron Run\n---------------\nStarted: " . date('r') . "\n");
        /** @var \mozzler\base\components\CronManager $cronManager */
        $cronManager = \Yii::$app->cronManager;
        $cronStats = $cronManager->run();
        $this->stdout("Finished: " . date('r') . "\n-- Stats --\n" . print_r($cronStats, true));

        return ExitCode::OK;
    }
}
