<?php
namespace mozzler\base\yii\rest;

use mozzler\base\models\Model as BaseModel;

class Serializer extends \yii\rest\Serializer
{
	
	public $itemEnvelope;
	
	/**
	 * Override base serializeModel() to adhere to scenario if defined
	 */
	protected function serializeModel($model) {
		if ($this->request->getIsHead()) {
            return null;
        }

        list($finalFields, $finalExpand) = $this->buildFinalFields($model);
        
        if ($this->itemEnvelope === null) {
	        return $model->toArray($finalFields, $finalExpand);
        }
        
        return [
	        $this->itemEnvelope => $model->toArray($finalFields, $finalExpand)
        ];
    }
    
    /**
     * Serializes a set of models.
     * 
     * Add support for model scenarios defining the activeAttributes
     * to return
     * 
     * @param array $models
     * @return array the array representation of the models
     */
    protected function serializeModels(array $models)
    {
        foreach ($models as $i => $model) {
            if ($model instanceof BaseModel) {
                list($finalFields, $finalExpand) = $this->buildFinalFields($model);
                $models[$i] = $model->toArray($finalFields, $finalExpand);
            }
            if ($model instanceof Arrayable) {
                $models[$i] = $model->toArray($fields, $expand);
            } elseif (is_array($model)) {
                $models[$i] = ArrayHelper::toArray($model);
            }
        }

        return $models;
    }

    protected function buildFinalFields($model) {
        $currentAction = \Yii::$app->controller->action;
        $model->scenario = $currentAction->resultScenario;

        list($fields, $expand) = $this->getRequestedFields();
        $activeAttributes = $model->activeAttributes();
        
        $finalFields = [];
        foreach ($fields as $attribute)
        {
	        if (in_array($attribute, $activeAttributes))
	        {
		        $finalFields[] = $attribute;
	        }
        }
        
        $finalExpand = [];
        foreach ($expand as $attribute)
        {
	        if (in_array($attribute, $activeAttributes))
	        {
		        $finalExpand[] = $attribute;
	        }
        }
        
        if (sizeof($finalFields) == 0) {
	        $finalFields = $activeAttributes;
        }

        return [$finalFields, $finalExpand];
    }
	
}