<?php

namespace mozzler\base\commands;

use mozzler\auth\models\oauth\OAuthClient;
use yii\console\ExitCode;
use yii\helpers\Console;


/**
 * Class DataController
 * @package mozzler\base\commands
 *
 * Uses the config/preload-data.php file.
 * You'll want to configure the 'name' in the common.php
 * Example config/console.php entry:
 * [
 * 'controllerMap' => [
 *  'data' => [
 *  'class' => 'mozzler\base\commands\DataController',
 * ]]
 *
 * Example config/preload-data.php:
 * <?php
 * return [
 *  OAuthClient::class => [
 *  [
 *   'name' => isset(\Yii::$app->name) ? \Yii::$app->name : "app",
 *   'client_id' => isset(\Yii::$app->name) ? strtolower(\Yii::$app->name) . '-id' : "app" . '-id',
 *   'client_secret' => isset(\Yii::$app->name) ? strtolower(\Yii::$app->name) . '-secret' : "app" . '-secret',
 *  ],
 * ]];
 *
 *
 */
class DataController extends BaseController
{

    public $dataFile = '/config/preload-data.php';
    protected $preloadData = null;

    /**
     * @throws \ErrorException
     */
    public function init()
    {
        if (($dataFile = \Yii::$app->basePath . $this->dataFile) && file_exists($dataFile)) {
            $this->preloadData = require($dataFile);
        } else {
            throw new \ErrorException("The Preload data file '" . \Yii::$app->basePath . "{$this->dataFile}' doesn't exist");
        }
    }

    /**
     * Preload Data
     *
     * Loads the /config/preload-data.php models
     *
     * @return int
     * @throws \yii\mongodb\Exception
     */
    public function actionInit()
    {
        /* @var \mozzler\base\components\Tools $baseTools */
        $baseTools = \Yii::$app->t;

        foreach ($this->preloadData as $className => $seedData) {
            $this->stdout("About to process ", Console::RESET);
            $this->stdout(count($seedData) . " entr" . (count($seedData) < 2 ? "y" : "ies"), Console::FG_GREEN);
            $this->stdout(" for class", Console::RESET);
            $this->stdout(" $className\n", Console::FG_CYAN);

            $newlyAddedCount = 0;
            foreach ($seedData as $indexTmp => $rowData) {
                /* @var \mozzler\base\models\Model $model */
                $model = $baseTools->getModel($className, $rowData, false);

                if (null === $model) {
                    $model = new $className();
                    $model->load([(substr($className, strrpos($className, '\\') + 1)) => $rowData]);

                    if (!$model->save(true, null, false)) {
                        echo sprintf("Row index %d failed, data \n %s \n", $indexTmp, print_r($rowData, true));
                    } else {
                        $newlyAddedCount++;
                    }
                }
            }

            $this->stdout("Saved ", Console::RESET);
            $this->stdout($newlyAddedCount, Console::FG_GREEN);
            $this->stdout(" new ", Console::RESET);
            $this->stdout("$className\n", Console::FG_CYAN);
        }

        return ExitCode::OK;
    }
}
