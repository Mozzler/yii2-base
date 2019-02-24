<?php

namespace mozzler\base\components;

use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class ConfigManager
{

    public $logs = [];

    public $runGetLatestParamConfigsOnInit = true; // Set to false if you want the original params contents not the updated DB ones
    public $className = 'mozzler\base\models\Config';

    public function init()
    {
        if ($this->runGetLatestParamConfigsOnInit) {
            \Yii::$app->params['config'] = $this->getLatestParamConfigs($this->className);
            \Yii::$app->params['configManagerHasInitialised'] = true;
        }
    }

    /**
     * Main function where params config values from config file are checked
     * and added to mondgoDB collection if non-existent
     */
    public function syncDefaultConfig($className)
    {
        // Get all configs from params file
        $paramsConfigs = $this->getParamsConfigs();

        // Get all existing configs from mongoDB
        $existingConfigs = $this->getExistingConfigs($className);

        // Loop each paramsConfigs, add if not exists
        foreach ($paramsConfigs as $key => $value) {

            $addFlag = true;
            foreach ($existingConfigs as $existingConfig) {
                $config = $existingConfig->toArray();
                if ($config['key_'] == $key) {
                    $addFlag = false;
                    break;
                }
            }

            if ($addFlag) {
                $data = [
                    'key_' => $key,
                    'value_' => $value
                ];
                $this->insertConfigItem($className, $data);
                $this->addLog('Added ' . $data['key_'] . ' to Config collection.');
            }
        }
    }

    /**
     * Insert config to mongoDB collection
     */
    protected function insertConfigItem($className, $data)
    {
        $config = \Yii::createObject($className);

        $config->load($data, "");
        $config->save();
    }

    /**
     * Take all configs written in mongoDB collection
     */
    protected function getExistingConfigs($className)
    {
        \Yii::createObject($className);
        $configs = $className::find()->all();

        return $configs;
    }

    /**
     * Get all config default key-value pairs from Yii::$app->params
     */
    protected function getParamsConfigs($options = null)
    {
        return \Yii::$app->params['config'];
    }


    /**
     * This can be used to set all the config params to the contents of the database
     */
    protected function getLatestParamConfigs($className)
    {
        // This updates the config params with what's in the database
        $existingDbConfigs = $this->getExistingConfigs($className);
        $existingDbEntries = [];
        foreach ($existingDbConfigs as $existingDbConfigIndex => $existingDbConfig) {
            $existingDbEntries[$existingDbConfig->key_] = $existingDbConfig->value_;
        }
        return ArrayHelper::merge(\Yii::$app->params['config'], $existingDbEntries);
    }

    /**
     * Provide logs to user when something has occured
     */
    protected function addLog($message, $type = 'info')
    {
        $this->logs[] = [
            'message' => $message,
            'type' => $type
        ];
    }

}