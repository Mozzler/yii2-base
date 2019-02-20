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
        /** @var \mozzler\base\components\CronManager $cronManager */
        $cronManager = \Yii::$app->cronManager;
        $cronStats = $cronManager->run();
        $this->stdout("Cron Run\n---------------\n" . print_r($cronStats, true));

        return ExitCode::OK;
    }
}
