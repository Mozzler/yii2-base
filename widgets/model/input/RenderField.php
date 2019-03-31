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

		// load the field class, if it exists
		$className = $modelField->widgets['input'];

        // Load the field object, if it exists
        $className = ArrayHelper::getValue($modelField->widgets, 'input.class');
        if (!empty($className) && class_exists($className)) {
            $fieldWidget = \Yii::createObject($modelField->widgets['input']);
		} else {
			// no specific field class, so fall back to the base class
			$config['viewName'] = $fieldType.'Field';
			$fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\input\\BaseField', $config);
		}
		
		return $fieldWidget::widget(["config" => $config]);
	}
	
}

?>