<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use kartik\select2\Select2 as BaseSelect2;
use yii\helpers\Html;
use yii\web\JsExpression;

class Select2 extends BaseSelect2 {
	// quick and dirty hack to deal with values being strings
	// TODO: do bug report or work around in a cleaner way
	public function init()
    {
        $this->pluginOptions['theme'] = $this->theme;
        \kartik\base\InputWidget::init();
        if (ArrayHelper::getValue($this->pluginOptions, 'tags', false)) {
            $this->options['multiple'] = true;
        }
        if ($this->hideSearch) {
            $css = ArrayHelper::getValue($this->pluginOptions, 'dropdownCssClass', '');
            $css .= ' kv-hide-search';
            $this->pluginOptions['dropdownCssClass'] = $css;
        }
        $this->initPlaceholder();
        if (!isset($this->data)) {
            $key = empty($this->value) ? '' : (string)$this->value;
            $val = empty($this->initValueText) ? $key : $this->initValueText;
            $this->data = [$key => $val];
        }
        Html::addCssClass($this->options, 'form-control');
        Html::addCssStyle($this->options, 'width:100%', false);
        $this->initLanguage();
        $this->registerAssets();
        $this->renderInput();
    }
}

class RelateOneField extends BaseField {
	
	public function defaultConfig()
	{
		return [
			'widgetConfig' => [
				'options' => ['placeholder' => 'Select {{ widget.model.getModelField(widget.attribute).label }} ...'],
				'pluginOptions' => [
					'allowClear' => false 
				]
			]
		];
	}
	
	public function run() {
		$config = $this->config(true);
		$field = $config['form']->field($config['model'], $config['attribute']);
		$modelField = $config['model']->getModelField($config['attribute']);
		
		// $selectConfig = [
		// 	'data' => ['hello', 'hi', 'hey']
		// ];

		$widgetConfig = $this->renderInput($config['form'], $config['model'], $config['attribute']);
		return $field->widget(Select2::className(), $widgetConfig);
	}

	public function renderInput($form, $model, $attribute) {

		$baseAttribute = substr($attribute,0,-3);
		$relatedModel = $model->getRelated($baseAttribute);
		
		if (!$relatedModel) {
			$field = $model->getModelField($attribute);
			$relatedModelNamespace = $field->relatedModel;

			if (isset($field->relatedModelField)) {
    			// This may be a flexible field that can be related to multiple model types
    			$relatedModelField = $field->relatedModelField;

    			if ($model->$relatedModelField)
	    			$relatedModelNamespace = $model->$relatedModelField;
			}
			
			$relatedModel = \Yii::createObject($relatedModelNamespace);
		}
		
		$listUrl = $relatedModel->getUrl('index');
		$viewUrl = $relatedModel->getUrl('view');
		
		$searchAttribute = 'name';
		//$modelNamespace = $model->modelNamespace();
		$modelNamespace = 'app.models.Section';
		
		$filterCode = "var fieldFilter = false;";
		/*if ($this->filter) {
			$filterFields = $model->activeAttributes();
			$filterCode = '
			// process filter for this model if specified
			var formData = $("#'.$form->id.'").serializeArray();
			var model = {"id": "'.$model->id.'"};
			var filterFields = '.json_encode($filterFields).'
			for (var f in formData) {
				var field = formData[f].name.match(/\\[(.*)\\]/);
				if (field)
					field = field[1];
				if (field != null && filterFields.indexOf(field) != -1)
					model[field] = formData[f].value;
			}
			
			var fieldFilter = {
				source: "'.$modelNamespace.'/'.$attribute.'",
				model: model
			}
			';
		}*/
	
		$widgetConfig = ArrayHelper::merge($this->defaultConfig()['widgetConfig'], [
			'options' => ['placeholder' => 'Search for a '.$relatedModel->getModelConfig("label")],
            'pluginOptions' => [
            	'allowClear' => true,
            	'ajax' => [
            		'url' => $listUrl,
					'dataType' => 'json',
					'method' => 'post',
					'data' => new JsExpression('
						function(params) {
							'.$filterCode.'
							
							var request = {};
							if (fieldFilter)
								request._ff = fieldFilter;
							return request;
						}'),
					'processResults' => new JsExpression('function(data,page) {
						var results = $.map(data.models, function (d,i) {
							return {id: d._id, text: d["'.$searchAttribute.'"]}
						});
						return {results: results}
					}'),
				],
			],
			'initValueText' => $relatedModel->$searchAttribute
		]);

		$field = $form->field($model, $attribute);
		
		/*if (isset($fieldConfig['parts']['{hintText}'])) {
			$field->hint($fieldConfig['parts']['{hintText}']);
		}*/
		return $widgetConfig;
	}
	
}

?>