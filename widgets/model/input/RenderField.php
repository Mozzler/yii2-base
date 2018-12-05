<?php
namespace mozzler\base\widgets\model\input;

use mozzler\base\widgets\BaseWidget;

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

		if (class_exists($className)) {
			$fieldWidget = \Yii::createObject($className, $config);
		} else {
			// no specific field class, so fall back to the base class
			$config['viewName'] = $fieldType.'Field';
			$fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\input\\BaseInputField', $config);
		}
		
		\Yii::trace(print_r(array_keys($config),true));
		
		return $fieldWidget::widget(["config" => $config]);
	}
	
}

?>