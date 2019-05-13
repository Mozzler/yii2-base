<?php
namespace mozzler\base\yii\data;

use yii\data\ActiveDataFilter as BaseActiveDataFilter;

class ActiveDataFilter extends BaseActiveDataFilter
{

    /**
     * Customise the attribute values to adhere to the Mozzler field
     * values
     */
    protected function filterAttributeValue($attribute, $value) {
        $value = parent::filterAttributeValue($attribute, $value);
        $model = $this->getSearchModel();
        $field = $model->getModelField($attribute);

        if ($field) {
            $value = $field->setValue($value);
        }

        return $value;
    }
}