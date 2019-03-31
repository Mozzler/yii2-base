<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;

class RenderField extends BaseWidget
{

    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            'wrapLayout' => true,
            'layoutConfig' => []
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config();

        // establish the type of field we need to render
        $modelField = $config['model']->getModelField($config['attribute']);
        $fieldType = $modelField->type;

        // Load the field object, if it exists
        $className = ArrayHelper::getValue($modelField->widgets, 'view.class');
        if (!empty($className) && class_exists($className)) {
            $modelConfig = $modelField->widgets['view'];
            unset($modelConfig['class']);
            $config = ArrayHelper::merge($config, $modelConfig);
            $fieldWidget = \Yii::createObject($modelField->widgets['view']);
        } else {
            // no specific field class, so fall back to the base class
            $fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\view\\BaseField');
        }

        $config['widgetHtml'] = $fieldWidget::widget(["config" => $config]);
        return $config;
    }

}

?>