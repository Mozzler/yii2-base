<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;

class RenderField extends BaseWidget
{

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'wrapLayout' => true,
            'layoutConfig' => []
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config($templatify);
        try {

            /** @var \mozzler\base\models\Model $model */
            $model = $config['model'];
            // establish the type of field we need to render
            $modelField = $model->getModelField($config['attribute']);

            $modelConfig = $modelField->widgets['view'];
            $config = ArrayHelper::merge($config, is_array($modelConfig) ? $modelConfig : []);

            // Load the field object, if it exists
            $className = ArrayHelper::getValue($modelField->widgets, 'view.class');
            if (!empty($className) && class_exists($className)) {
                $fieldWidget = \Yii::createObject($className);
            } else {
                // no specific field class, so fall back to the base class
                $fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\view\\BaseField');
            }

            $config['widgetHtml'] = $fieldWidget->widget(["config" => $config]);
        } catch (\Throwable $exception) {
            return $config;
        }
        return $config;
    }

}

