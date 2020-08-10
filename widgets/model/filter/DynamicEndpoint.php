<?php
namespace mozzler\base\widgets\model\filter;

use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

class DynamicEndpoint extends BaseField
{
	
	public function defaultConfig()
	{
		return [
			'widgetConfig' => [
				'options' => [
                    'placeholder' => 'Select {{ widget.model.getModelField(widget.attribute).label }}...',
                    'multiple' => false
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

        $ajax = [
            'url' => $config['endpoint'],
            'accepts' => [
                'text' => 'application/json'
            ],
            'dataType' => 'json',
            'method' => 'get',
            'data' => new JsExpression('
                function(params) {
                    let request = '.($config['params'] ? json_encode($config['params']) : '{}').';
                    request.term = params.term;
                    return request;
                }'),
            'processResults' => new JsExpression('function(data,page) {
                var results = $.map(data.items, function (d,i) {
                    return {id: d._id, text: d.value}
                });
                return {results: results}
            }')
        ];
		
		$selectConfig = ArrayHelper::merge([
            'pluginOptions' => [
                'ajax' => $ajax
            ]
        ], $config['widgetConfig']);
		
		return $field->widget(Select2::className(), $selectConfig);
	}
	
}

