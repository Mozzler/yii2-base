<?php

namespace mozzler\base\widgets\model\filter;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\ArrayHelper;

class RenderField extends BaseWidget
{

    public function run()
    {
        $config = $this->config();

        $model = $config['model'];
        $attribute = $config['attribute'];

        // establish the type of field we need to render
        $modelField = $model->getModelField($attribute);
        if (!$modelField) {
            \Yii::warning("Non-existent attribute $attribute");//(".$config['attribute'].") specified in search filter");
            return;
        }

        $fieldType = $modelField->type;


        // Load the field object, if it exists
        $className = ArrayHelper::getValue($modelField->widgets, 'filter.class');
        \Yii::debug("RenderField->run() is processing the className: " . json_encode($className));
        if (!empty($className) && class_exists($className)) {
            \Yii::debug("RenderField->run() is processing the filter widget: " . json_encode($modelField->widgets));
            $fieldWidget = \Yii::createObject($modelField->widgets['filter']);
        } else {
            // no specific field class, so fall back to the base class
            $config['viewName'] = $fieldType . 'Field';
            $fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\filter\\BaseField', $config);
        }

        return $fieldWidget::widget(["config" => $config]);
    }

}

?>