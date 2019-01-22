<?php
namespace mozzler\base\widgets\model\filter;

use mozzler\base\widgets\BaseWidget;

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

		// load the field class, if it exists
		$className = $modelField->widgets['filter'];

		if (class_exists($className)) {
			$fieldWidget = \Yii::createObject($className, $config);
		} else {
			// no specific field class, so fall back to the base class
			$config['viewName'] = $fieldType.'Field';
			$fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\filter\\BaseField', $config);
		}
		
		return $fieldWidget::widget(["config" => $config]);
	}
	
}

?>