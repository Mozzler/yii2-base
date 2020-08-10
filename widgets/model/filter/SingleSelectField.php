<?php
namespace mozzler\base\widgets\model\filter;

use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

class SingleSelectField extends BaseField
{
	
	public function defaultConfig()
	{
		return [
			'widgetConfig' => [
				'options' => [
                    'placeholder' => 'Select {{ widget.model.getModelField(widget.attribute).label }}...',
                    'multiple' => true
                ],
				'pluginOptions' => [
					'allowClear' => true
				]
			]
		];
	}
	
	public function run() {
		$config = $this->config(true);
		$field = $config['form']->field($config['model'], $config['attribute']);
        $modelField = $config['model']->getModelField($config['attribute']);
        
        //$options = ArrayHelper::merge(['' => ''], $modelField->options);
        $options = $modelField->options;
		
		$selectConfig = ArrayHelper::merge([
            'data' => $options,
        ],$config['widgetConfig']);
		
		return $field->widget(Select2::className(), $selectConfig);
	}
	
}

