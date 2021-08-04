<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\fields\File;
use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\VarDumper;

class FileField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "div",
            "options" => [
                "class" => "view-model-field-file",
            ],
            "model" => null,
            "attribute" => null,
            'value' => '',
            'link' => '',
        ]);
    }

    public function config($templatify = false)
    {
        $config = parent::config();

        // Looks up the file info
        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attribute = $config['attribute'];
            if ($config['model']->$attribute) {
                /** @var \mozzler\base\models\File $fileModel */
                $fileModel = $this->lookupFile($config['model']->$attribute, $attribute);
                if ($fileModel) {
                    // Some nice to have info
//                    \Yii::debug("The file model: " . json_encode($fileModel->toScenarioArray()));
                    $config['value'] = $fileModel->getFileValue(); // The name
                    $config['link'] = $fileModel->getUrl('download');
                    $config['size'] = $fileModel->getFileDownloadSizeReadable();
                }
            }
        }

        return $config;
    }

    public function lookupFile($objectId)
    {
        $fileField = \Yii::createObject(File::class);
        // Check if the File exists
        try {
            /** @var \mozzler\base\models\File $fileModel */
            $fileModel = \Yii::$app->t::getModel($fileField->relatedModel, $objectId);
        } catch (\Throwable $exception) {
            \Yii::error("Error getting the file with objectId: " . VarDumper::export($objectId) . "\nError: " . \Yii::$app->t::returnExceptionAsString($exception));
            $fileModel = null;
        }
        return $fileModel;
    }


}
