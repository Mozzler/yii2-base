<?php

namespace mozzler\base\widgets\model;

use app\models\CustomerGroup;
use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;
use yii\web\View;
use yii\helpers\Json;
use yii\web\JsExpression;

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

        $fieldsVisibleWhen = [];
        $view = \Yii::$app->controller->getView();
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

            if (!empty($modelField->visibleWhen)) {
                // We need to save the functions as a JsExpression so the Json encoding deals with them correctly
                $fieldsVisibleWhen[$attribute] = new JsExpression($modelField->visibleWhen);
            }
        }

        if ($hasFileUpload) {
            $config['formConfig']['options']['enctype'] = 'multipart/form-data';
        }

        // --------------------------------
        //  Add the JS info to the page
        // --------------------------------
        // Used for the showing/hiding of fields, see widgets/model/CreateModel.ready.js
        $view->registerJs(
            'var mozzlerFieldsVisibleWhen = ' . Json::encode($fieldsVisibleWhen) . ';',
            View::POS_HEAD,
            'mozzlerFieldsVisibleWhen'
        );
        $view->registerJs(
            'var mozzlerMainWidgetId = ' . Json::encode($config['id']) . ';',
            View::POS_HEAD,
            'mozzlerMainWidgetId'
        );
        $view->registerJs(
            'var mozzlerMainModelClassName = ' . Json::encode($t::getModelClassName($model)) . ';', //
            View::POS_HEAD,
            'mozzlerMainModelClassName'
        );
        return $config;
    }
}

