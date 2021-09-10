<?php
namespace mozzler\base\widgets\model\input;

use kartik\select2\Select2 as BaseSelect2;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\helpers\Html;
use mozzler\base\components\Tools;

class Select2 extends BaseSelect2 {
	// Deal with values being strings
	public function init()
    {
        parent::init();

        if (!isset($this->data)) {
            $key = empty($this->value) ? '' : (string)$this->value;
            $val = empty($this->initValueText) ? $key : $this->initValueText;
            $this->data = [$key => $val];
        }
    }
}

class RelateOneField extends BaseField
{
	
	public function defaultConfig()
	{
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => []
		]);
    }
    
    public function run() {
        $config = $this->config(true);
        $attribute = $config['attribute'];
        $model = $config['model'];
        $form = $config['form'];

        $field = $config['form']->field($config['model'], $config['attribute'], $config['fieldOptions']);
        $modelField = $config['model']->getModelField($attribute);

        $baseAttribute = $attribute; //substr($attribute,0,-2);
        $relatedModel = $model->getRelated($baseAttribute);
        
        if (!$relatedModel) {
            $modelField = $config['model']->getModelField($attribute);
            $relatedModelNamespace = $modelField->relatedModel;

            if (isset($modelField->relatedModelField)) {
                // This may be a flexible field that can be related to multiple model types
                $relatedModelField = $modelField->relatedModelField;

                if ($model->$relatedModelField) {
                    $relatedModelNamespace = $model->$relatedModelField;
                }
            }
            
            $relatedModel = Tools::createModel($relatedModelNamespace);
        }
        
        $listUrl = $relatedModel->getUrl('index');
        $viewUrl = $relatedModel->getUrl('view');
        
        $searchAttribute = $relatedModel->getModelConfig('searchAttribute');
        
        $filterCode = "var fieldFilter = false;";
        /*if ($modelField->filter) {
            $filterFields = $model->activeAttributes();
            $filterCode = '
            // process filter for this model if specified
            var formData = $("#'.$form->id.'").serializeArray();
            var model = {"id": "'.$model->id().'"};
            var filterFields = '.json_encode($filterFields).'
            for (var f in formData) {
                var field = formData[f].name.match(/\\[(.*)\\]/);
                if (field)
                    field = field[1];
                if (field != null && filterFields.indexOf(field) != -1)
                    model[field] = formData[f].value;
            }
            
            var fieldFilter = {
                source: "'.$model->collectionName().'/'.$attribute.'",
                model: model
            }
            ';
        }*/
    
    
        $config['widgetConfig'] = ArrayHelper::merge($config['widgetConfig'], [
            'options' => ['placeholder' => 'Search for a '.$relatedModel->getModelConfig("label")],
            'pluginOptions' => [
                'allowClear' => true,
                'tags' => $modelField->allowUserDefined,
                'ajax' => [
                    'url' => $listUrl,
                    'accepts' => [
                        'text' => 'application/json'
                    ],
                    'dataType' => 'json',
                    'method' => 'get',
                    'data' => new JsExpression('
                        function(params) {
                            '.$filterCode.'
                            var request = {
                                '.$relatedModel->formName().': {
                                    '.$searchAttribute.': params.term
                                }
                            };
                            if (fieldFilter)
                                request._ff = fieldFilter;
                            return request;
                        }'),
                    'processResults' => new JsExpression('function(data,page) {
                        var results = $.map(data.items, function (d,i) {
                            return {id: d._id, text: d["'.$searchAttribute.'"]}
                        });
                        return {results: results}
                    }')
                ],
            ],
            'initValueText' => $relatedModel->$searchAttribute
        ]);

        /*$fieldConfig = $this->renderFieldConfig($fieldConfig);
        $field = $form->field($model, $attribute, $fieldConfig);
        
        if (isset($fieldConfig['parts']['{hintText}'])) {
            $field->hint($fieldConfig['parts']['{hintText}']);
        }
        
        $this->_field = $field;*/
        
        $field = $form->field($model, $attribute);
        $output = $field->widget(Select2::className(), $config['widgetConfig']);

        return $output;
	}
	
}

