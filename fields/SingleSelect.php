<?php
namespace mozzler\base\fields;

use yii\helpers\ArrayHelper;

class SingleSelect extends Base
{

    public $type = 'SingleSelect';
    public $operator = "=";
    public $options = [];

    public function defaultRules()
    {
        return [
            'string' => [
                'max' => 255
            ]
        ];
    }

    public function rules()
    {
        \Codeception\Util\Debug::debug("About to check the " . __METHOD__ . " for the options: " . var_export($this->options, true));
        \Codeception\Util\Debug::debug("Checking that the range is in: " . var_export(array_keys($this->options), true));
        return ArrayHelper::merge(parent::rules(), [
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

?>
