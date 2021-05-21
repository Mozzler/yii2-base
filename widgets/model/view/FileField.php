<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\fields\File;
use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

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
                    $config['value'] = $this->getFileValue($fileModel, $attribute);
                    $config['link'] = $this->getFileDownloadLinkValue($fileModel->getId());
                    $config['size'] = $this->getFileDownloadSizeReadable($fileModel->size);

                }
            }
        }

        return $config;
    }

    public function getFileDownloadSizeReadable($sizeInBytes)
    {
        if (!$sizeInBytes) {
            return 'Empty';
        }

        if ($sizeInBytes >= 1073741824) {
            return number_format($sizeInBytes / 1024 / 1024 / 1024, 2) . ' GB';
        } elseif ($sizeInBytes >= 1048576) {
            return number_format($sizeInBytes / 1024 / 1024, 2) . ' MB';
        } elseif ($sizeInBytes >= 1024) {
            return number_format($sizeInBytes / 1024, 2) . ' KB';
        } else {
            return $sizeInBytes . ' bytes';
        }
    }

    public function getFileDownloadLinkValue($fileId)
    {
        // Where the download link points to
        return Url::to(['file/download', 'id' => (string)$fileId]);
    }

    public function lookupFile($objectId)
    {
        $fileField = \Yii::createObject(File::class);
        // Check if the File exists
        try {
            /** @var \mozzler\base\models\File $fileModel */
            $fileModel = \Yii::$app->t::getModel($fileField->relatedModel, $objectId);
        } catch (\Throwable $exception) {
            \Yii::error("Error getting the file with objectId: $objectId\nError: " . \Yii::$app->t::returnExceptionAsString($exception));
            $fileModel = null;
        }
        return $fileModel;
    }

    /**
     * @param $fileModel
     * @param $fileId
     * @return string
     *
     * Returns the string output, aiming for the original filename, the filename or the fileId if needed
     */
    public function getFileValue($fileModel, $fileId)
    {
        if (!$fileModel) {
            return $fileId;
        }
        if ($fileModel->originalFilename) {
            return $fileModel->originalFilename;
        }
        if ($fileModel->filename) {
            return $fileModel->filename;
        }
        return (string)$fileId;
    }

}
