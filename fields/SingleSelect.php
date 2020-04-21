<?php
namespace mozzler\base\fields;

use yii\helpers\ArrayHelper;

class SingleSelect extends Base
{

    public $type = 'SingleSelect';
    public $filterType = "=";
    public $options = [];

    public function defaultRules()
    {
        return ArrayHelper::merge(parent::defaultRules(), [
            'string' => [
                'max' => 255
            ],
            'in' => ['range' => array_keys($this->options), 'message' => 'Invalid option specified for ' . $this->label]
        ]);
    }

    /**
     * Take an array of option keys and return the values
     */
    public function getOptionLabels($options = [])
    {
        if (!is_array($options)) {
            $options = [$options];
        }

        $result = [];
        foreach ($options as $option) {
            if (isset($this->options[$option])) {
                $result[] = $this->options[$option];
            } else {
                \Yii::warning('Invalid select option specified (' . $option . ' for field ' . $this->attribute . ')');
            }
        }

        return $result;
    }

}

