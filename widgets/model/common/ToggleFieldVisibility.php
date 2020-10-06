<?php

namespace mozzler\base\widgets\model\common;

use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;
use yii\web\View;
use yii\helpers\Json;
use yii\web\JsExpression;

class ToggleFieldVisibility extends BaseWidget
{

    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'widget-model-toggle-visibility'
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
            'model' => null,
            'formId' => null
        ];
    }


    // Take $config and process it to generate final config
    public function code($templatify = false)
    {
        $config = $this->config();
        /** @var Model $model */
        $model = $config['model'];

        $fieldsVisibleWhen = [];

        $modelFields = $model->getCachedModelFields();
        
        foreach ($modelFields as $modelFieldKey => $modelField) {
            if (!empty($modelField) && !empty($modelField->visibleWhen)) {
                // We need to save the functions as a JsExpression so the Json encoding deals with them correctly
                $fieldsVisibleWhen[$modelFieldKey] = new JsExpression($modelField->visibleWhen);
            }
        }

        $this->outputJsData([
            'formId' => $config['formId'],
            'fieldsVisibleWhen' => $fieldsVisibleWhen
        ]);
        return $config;
    }
}

