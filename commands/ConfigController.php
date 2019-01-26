<?php

namespace mozzler\base\commands;

use Yii;
use yii\console\Controller;
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
class ConfigController extends Controller
{
    /**
     * This command preloads the config collection with defined default values
     *
     * @return int Exit code
     */
    public function actionInit()
    {
        // set the model class
        $modelClass = 'mozzler\base\models\Config';

        // Load the ConfigManager Class
        $configManager = \Yii::createObject('mozzler\base\components\ConfigManager');

        // execute the configManager->syncDefaultValues
        $configManager->syncDefaultConfig($modelClass);

        $this->outputLogs($configManager->logs);

        return ExitCode::OK;
    }

    protected function outputLogs($logs) {
        foreach ($logs as $entry) {
            $this->stdout($entry['message']."\n", $entry['type'] == 'error' ? Console::FG_RED : null);
        }
    }
}
