<?php

namespace mozzler\base\components;

use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class IndexManager
{

    public $defaultModelPaths = ['@app/models/', '@mozzler/base/models/', '@mozzler/auth/models/', '@mozzler/auth/models/oauth/'];
    public $logs = [];

    public function syncModelIndexes($className)
    {
        $modelIndexes = $this->getModelIndexes($className);
        $existingIndexes = $this->getExistingIndexes($className);

        $collection = $this->getCollection($className);

        foreach ($existingIndexes as $eIndex) {
            // Get the existingIndex Name
            $indexName = $eIndex['name'];

            // Always skip the _id index by default this index
            // is made by mongo and we don't want to mess with it
            if ($indexName == "_id_") {
                continue;
            }

            // Check if existingIndex is present or changed in modelIndexes

            $existingIndexOptions = [
                'unique' => ArrayHelper::getValue($eIndex, 'unique'),
            ];

            if (isset($modelIndexes[$indexName])) {
                if ($eIndex['key'] == $modelIndexes[$indexName]['columns']) {

                    foreach ($existingIndexOptions as $optionKey => $optionValue) {
                        if (!empty($modelIndexes[$indexName]['options']) && $optionValue != ArrayHelper::getValue($modelIndexes[$indexName]['options'], $optionKey)) {
                            $this->handleUpdate($collection, $indexName, $modelIndexes[$indexName]);
                        }
                    }

                } else {
                    $this->handleUpdate($collection, $indexName, $modelIndexes[$indexName]);
                }
            } else {
                $this->handleDelete($collection, $indexName);
            }


        }

        $this->handleCreate($collection, $modelIndexes, $existingIndexes);
    }

    /**
     * Handle updating existing indexes if they have changed
     */
    protected function handleUpdate($collection, $indexName, $indexConfig)
    {
        $collection->dropIndexes($indexName);

        $options = $indexConfig['options'];
        $options['name'] = $indexName;

        $collection->createIndex($indexConfig['columns'], $options);

        $this->addLog("Updated index: " . $indexName);
    }

    /**
     * Handle creating new indexes if they don't exist
     * @param $collection \MongoCollection
     * @param $modelIndexes array
     * @param $existingIndexes array
     */
    protected function handleCreate($collection, $modelIndexes, $existingIndexes)
    {
        foreach ($modelIndexes as $indexName => $indexConfig) {

            $addFlag = true;
            foreach ($existingIndexes as $existingIndex) {

                if ($existingIndex['name'] == $indexName) {
                    $addFlag = false;
                    break;
                }
            }

            if ($addFlag) {
                try {
                    $options = isset($indexConfig['options']) ? $indexConfig['options'] : [];
                    $options['name'] = $indexName;

                    if ($collection->createIndex($indexConfig['columns'], $options)) {
                        $this->addLog("Creating index: $indexName");
                    } else {
                        $this->addLog("Unable to create index: $indexName", 'error');
                    }
                } catch (\Exception $e) {
                    $this->addLog("Exception creating: $indexName (" . $e->getMessage() . ")", 'error');
                }
            }
        }
    }

    /**
     * Handle deleting existing indexes if they have been removed
     */
    protected function handleDelete($collection, $indexName)
    {
        $collection->dropIndexes($indexName);

        $this->addLog("Deleted index: " . $indexName);
    }

    protected function getExistingIndexes($className)
    {
        $collection = $this->getCollection($className);
        $indexes = $collection->listIndexes();

        return $indexes;
    }

    protected function getModelIndexes($className)
    {
        $model = \Yii::createObject($className);
        return $model->modelIndexes();
    }

    protected function getCollection($className)
    {
        return $className::getCollection();
    }

    protected function addLog($message, $type = 'info')
    {
        $this->logs[] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Function takes a parameter $modelPaths array
     * and extracts all valid PHP Class files in each
     * directory (non-recursive)
     * and assigns them to model for return.
     *
     * Return array looks:
     * [
     *       [0] => app\models\Config
     *       [1] => app\models\Device
     * ]
     *
     */
    public function buildModelClassList($modelPaths = null)
    {
        if (empty($modelPaths)) {
            $modelPaths = $this->defaultModelPaths;
        }
        $models = [];

        foreach ($modelPaths as $modelPath) {

            try {
                $files = FileHelper::findFiles(\Yii::getAlias($modelPath), ['only' => ['*.php'], 'recursive' => FALSE]);

                foreach ($files as $file) {
                    // substr 		- remove @ from the beginning of the modelPath
                    // str_replace 	- substitute / (backward slash) with \ forward slash
                    // rtrim 		- make sure that forward slash is present
                    // pathinfo 	- remove the extension name of PHP files
                    $path = \rtrim(\str_replace("/", "\\", \substr($modelPath, 1)), '\\') . '\\' . \pathinfo($file, PATHINFO_FILENAME);

                    // Create an Object base on path
                    $modelClass = \Yii::createObject($path);

                    // Test if the object consist `modelIndexes` method before adding to models
                    if (method_exists($modelClass, 'modelIndexes')) {
                        $models[] = $path;
                    }
                }

            } catch (\yii\base\InvalidArgumentException $e) {
                // Catch if exception if dir argument not a directory
                // continue and ignore the error
                continue;
            }

        }

        return $models;
    }

}