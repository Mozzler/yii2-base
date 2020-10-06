<?php

namespace mozzler\base\widgets\model;

use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;
use yii\helpers\Json;

class CreateModel extends BaseWidget
{

    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'widget-model-create row'
            ],
            'container' => [
                'tag' => 'div',
                'options' => [
                    'class' => 'col-md-12'
                ]
            ],
            'formConfig' => [
                'options' => []
            ],
            'model' => null
        ];
    }

    // take $config and process it to generate final config
    public function code($templatify = false)
    {
        $config = $this->config();
        /** @var Model $model */
        $model = $config['model'];
        $t = new \mozzler\base\components\Tools;

        $config['attributes'] = $model->activeAttributes();
        $config['items'] = [];
        $config['hiddenItems'] = [];
        $hasFileUpload = false;

        foreach ($config['attributes'] as $attribute) {
            $modelField = $model->getModelField($attribute);
            if (!$modelField) {
                \Yii::warning("Non-existent attribute ($attribute) specified in scenario " . $model->scenario . " on " . $model->className());
                continue;
            }

            if ($modelField->hidden) {
                $config['hiddenItems'][] = $attribute;
            } else {
                $config['items'][] = $attribute;
            }

            if ($modelField->type == 'FileUpload') {
                $hasFileUpload = true;
            }
        }

        if ($hasFileUpload) {
            $config['formConfig']['options']['enctype'] = 'multipart/form-data';
        }

        return $config;
    }
}

