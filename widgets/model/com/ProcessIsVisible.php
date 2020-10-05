<?php

namespace mozzler\base\widgets\model\com;

use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;
use yii\web\JsExpression;

class ProcessIsVisible extends BaseWidget
{


    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'widget-model-toggle-fields-visibility'
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
//
//    // Take $config and process it to generate final config
//    public function code($templatify = false)
//    {
//        $config = $this->config();
//        /** @var Model $model */
//        $model = $config['model'];
//
//        $fieldsVisibleWhen = [];
////        $view = \Yii::$app->controller->getView();
//
//        $modelFields = $model->getCachedModelFields();
//        foreach ($modelFields as $modelFieldKey => $modelField) {
//            if (!empty($modelField) && !empty($modelField->visibleWhen)) {
//                // We need to save the functions as a JsExpression so the Json encoding deals with them correctly
//                $fieldsVisibleWhen[$modelFieldKey] = new JsExpression($modelField->visibleWhen);
//            }
//        }
//
//        // --------------------------------
//        //  Add the JS info to the page
//        // --------------------------------
//        // Used for the showing/hiding of fields, see widgets/model/CreateModel.ready.js
////        $view->registerJs(
////            'var mozzlerFieldsVisibleWhen = ' . Json::encode($fieldsVisibleWhen) . ';',
////            View::POS_HEAD,
////            'mozzlerFieldsVisibleWhen'
////        );
//
////        $this->outputJsData(['mozzlerFieldsVisibleWhen' => Json::encode($fieldsVisibleWhen)]);
//        return $config;
//    }
}

