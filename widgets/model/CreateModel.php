<?php

namespace mozzler\base\widgets\model;

//use app\models\CustomerGroup;
use mozzler\base\models\Model;
use mozzler\base\widgets\BaseWidget;
//use yii\web\View;
use yii\helpers\Json;
//use yii\web\View as WebView;
use mozzler\base\widgets\model\common\ToggleFieldVisibility;

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

//        $view = \Yii::$app->controller->getView();
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

        // --------------------------------
        //  Add the JS info to the page
        // --------------------------------
        // Used for the showing/hiding of fields, see widgets/model/CreateModel.ready.js

        $this->outputJsData([
            'mozzlerMainModelClassName' => [
                Json::encode($t::getModelClassName($model))
            ],
            'mozzlerMainWidgetId' => [
                Json::encode($this->id)
            ]
        ]);

//        $view->registerJs(
//            'var mozzlerFieldsVisibleWhen = ' . Json::encode($fieldsVisibleWhen) . ';',
//            View::POS_HEAD,
//            'mozzlerFieldsVisibleWhen'
//        );
//        $this->outputJsData([
//            'mozzlerMainWidgetId' => [
//                Json::encode($t::getModelClassName($model))
//            ]
//        ]);
//        $view->registerJs(
//            'var mozzlerMainWidgetId = ' . Json::encode($this->id) . ';',
//            View::POS_HEAD,
//            'mozzlerMainWidgetId'
//        );
//        $view->registerJs(
//            'var mozzlerMainModelClassName = ' . Json::encode($t::getModelClassName($model)) . ';', //
//            View::POS_HEAD,
//            'mozzlerMainModelClassName'
//        );
        return $config;
    }
}

