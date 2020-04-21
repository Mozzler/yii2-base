<?php

namespace mozzler\base\widgets\model\input;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;

class RenderField extends BaseWidget
{

    public function run()
    {
        $config = $this->config();

        // establish the type of field we need to render
        $modelField = $config['model']->getModelField($config['attribute']);
        $fieldType = $modelField->type;

        // If the field is set to readOnly then set the input field attribute as disabled.
        if ($modelField->readOnly) {
            $modelField->widgets['input'] = ArrayHelper::merge($modelField->widgets['input'], ['fieldOptions' => ['inputOptions' => ['disabled' => 'disabled', 'class' => 'form-control']]]);
        }

        // load the field class, if it exists
        $className = ArrayHelper::getValue($modelField->widgets, 'input.class');
        if (!empty($className) && class_exists($className)) {
            $fieldWidget = \Yii::createObject($className);
        } else {
            // no specific field class, so fall back to the base class
            $config['viewName'] = $fieldType . 'Field';
            $fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\input\\BaseField', $config);
        }

        $modelConfig = $modelField->widgets['input'];
        unset($modelConfig['class']);
        $config = ArrayHelper::merge($config, is_array($modelConfig) ? $modelConfig : []);

        return $fieldWidget::widget(["config" => $config]);
    }

}

