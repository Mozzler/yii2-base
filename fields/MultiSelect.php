<?php
namespace mozzler\base\fields;

use yii\helpers\ArrayHelper;

class MultiSelect extends Base
{

    public $type = 'MultiSelect';
    public $operator = "=";
    public $options = [];

    public function defaultRules()
    {
        return ArrayHelper::merge(parent::defaultRules(), [
            'string' => new \yii\helpers\UnsetArrayValue()
        ]);
    }

    // allowArray
    public function rules()
    {
        \Codeception\Util\Debug::debug(__METHOD__ . " About to check the " . __METHOD__ . " for the options: " . var_export($this->options, true));
        \Codeception\Util\Debug::debug(__METHOD__ . " Checking that the range is in: " . var_export(array_keys($this->options), true));
        $rules = ArrayHelper::merge(parent::rules(), [
            'each' => [ 'rule' => [
                'in' => [
                    'range' => array_keys($this->options),
                    'message' => 'Invalid option specified for ' . $this->label,
                    'allowArray' => true,],
            ]]
        ]);

        \Codeception\Util\Debug::debug(__METHOD__ . " Returning the rules - " . var_export($rules, true));
        return $rules;
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
