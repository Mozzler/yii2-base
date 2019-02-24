<?php

namespace mozzler\base\commands;

use Yii;
use yii\helpers\Console;
use yii\console\ExitCode;

// Sample config params settings
// 
// $params = [
//     "config" => [
//         "defaults" => [
//             "Unsubscribe Email Address" => "test@email.com",
//             "Api Disabled" => "true"
//         ]
//     ]
// ];

/**
 * This class offers easy way to implement `config` collection.
 */
class ConfigController extends BaseController
{
    /**
     * This command preloads the config collection with defined default values
     *
     * By default it will pull the existing DB entries first so you are only adding any new unset entries
     * However if you have $useConfigDbDefaults as false then you will overwrite the database with what's in the params['config'] array
     *
     * @param bool $useConfigDbDefaults
     * @throws \yii\base\InvalidConfigException
     * @return int Exit code
     */
    public function actionInit()
    {
        // set the model class
        $modelClass = 'mozzler\base\models\Config';

        // Load the ConfigManager Class
        $configManager = \Yii::createObject(['class' => 'mozzler\base\components\ConfigManager']);

        // execute the configManager->syncDefaultValues
        $configManager->syncDefaultConfig($modelClass);

        $this->outputLogs($configManager->logs);

        return ExitCode::OK;
    }

    protected function outputLogs($logs)
    {
        foreach ($logs as $entry) {
            $this->stdout($entry['message'] . "\n", $entry['type'] == 'error' ? Console::FG_RED : null);
        }
    }
}
