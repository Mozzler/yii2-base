<?php
namespace mozzler\base\yii\rest;

class Serializer extends \yii\rest\Serializer
{
	
	/**
	 * Override base serializeModel() to adhere to scenario if defined
	 */
	protected function serializeModel($model) {
		if ($this->request->getIsHead()) {
            return null;
        }

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
        
        return $model->toArray($finalFields, $finalExpand);
	}
	
}