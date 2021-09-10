<?php
namespace mozzler\base\widgets\model\input;

use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

class SingleSelectField extends BaseField
{
	
	public function defaultConfig()
	{
        return ArrayHelper::merge(parent::defaultConfig(), [
			'widgetConfig' => [
				'options' => ['placeholder' => 'Select {{ widget.model.getModelField(widget.attribute).label }} ...'],
				'pluginOptions' => [
					'allowClear' => false
				]
			]
		]);
	}
	
	public function run() {
		$config = $this->config(true);
		$field = $config['form']->field($config['model'], $config['attribute'], $config['fieldOptions']);
		$modelField = $config['model']->getModelField($config['attribute']);
		
		$selectConfig = ArrayHelper::merge([
			'data' => $modelField->options
		],$config['widgetConfig']);
		
		return $field->widget(Select2::className(), $selectConfig);
	}
	
}

