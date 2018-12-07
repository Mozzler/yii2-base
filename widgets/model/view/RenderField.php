<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\BaseWidget;

class RenderField extends BaseWidget
{
	
	public function defaultConfig() {
		return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
			'wrapLayout' => true,
			'layoutConfig' => []
		]);
	}
	
	public function config($templatify=true)
	{
		$config = parent::config();
		
		// establish the type of field we need to render
		$modelField = $config['model']->getModelField($config['attribute']);
		$fieldType = $modelField->type;

		// load the field class, if it exists
		$className = '\\'.$modelField->widgets['view'];

		if (class_exists($className)) {
			$fieldWidget = \Yii::createObject($className);
		} else {
			// no specific field class, so fall back to the base class
			$fieldWidget = \Yii::createObject('\\mozzler\\base\\widgets\\model\\view\\BaseField');
		}
		
		$config['widgetHtml'] = $fieldWidget::widget(["config" => $config]);
		return $config;
	}
	
}

?>