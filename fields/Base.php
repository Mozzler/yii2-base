<?php

namespace mozzler\base\fields;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class Base extends Component
{

    /** @var string */
    public $type = 'Base';
    /** @var string $label */
    public $label;
    /** @var string */
    public $hint;
    /** @var array */
    public $config = [];
    /** @var array */
    public $rules = [];
    /** @var \mozzler\base\models\Model $model */
    public $model;
    /** @var string $attribute */
    public $attribute;
    /** @var string */
    public $filterType = "=";
    /** @var mixed */
    public $default = null;
    /** @var bool $required */
    public $required = false;
    /** @var array */
    public $widgets = [];
    /** @var bool */
    public $hidden = false;
    /** @var array */
    public $options = [];

    public $formula = null;
    /** @var null|string $visibleWhen */

    //  Set to a JS function to show/hide the input field based on the form data
    // e.g 'function (attribute, value, attributesMap) { return "' . self::ANSWER_TYPE_SINGLE_SELECT . '" === attributesMap.answerType; }'
    // e.g "function (attribute, value, attributesMap) { return !['" . implode([self::LINK_TYPE_NONE, self::LINK_TYPE_INTERNAL], "','") . "'].includes(attributesMap['linkType']); }",
    // Requires the JS code to be valid, don't forget to "quote" strings. You'll likely also want to set associated required rules
    public $visibleWhen = null;

    /**
     * Should this field be saved to the database?
     */
    public $save = true;

    //public $format = 'text';	// see i18n/Formatter
    //public $options;
    //public $filter;
    //public $help;
    //public $multiple;
    public $readOnly = false;

    public function init()
    {
        parent::init();

        // set default input / view widgets based on this field type
        $this->widgets['input'] = ArrayHelper::merge([
            'class' => 'mozzler\base\widgets\model\input\\' . $this->type . 'Field',
            'config' => []
        ], isset($this->widgets['input']) && is_array($this->widgets['input']) ? $this->widgets['input'] : []);

        if ($this->hidden) {
            $this->widgets['input']['class'] = 'mozzler\base\widgets\model\input\\HiddenField';
        }

        $this->widgets['view'] = ArrayHelper::merge([
            'class' => 'mozzler\base\widgets\model\view\\' . $this->type . 'Field',
            'config' => []
        ], isset($this->widgets['view']) && is_array($this->widgets['view']) ? $this->widgets['view'] : []);

        $this->widgets['filter'] = ArrayHelper::merge([
            'class' => 'mozzler\base\widgets\model\filter\\' . $this->type . 'Field',
            'config' => []
        ], isset($this->widgets['filter']) && is_array($this->widgets['filter']) ? $this->widgets['filter'] : []);
    }

    /**
     * format: [validator, parameter => value]
     */
    public function rules()
    {
        $rules = ArrayHelper::merge($this->defaultRules(), $this->rules);

        if ($this->required && !isset($customRules['required'])) {
            $rules['required'] = ['message' => $this->label . ' cannot be blank'];

            // required may be an array of scenarios where it is required
            if (is_array($this->required)) {
                $rules['required']['on'] = $this->required;
            }
        }

        if ($this->default) {
            $rules['default'] = ['value' => $this->default];
        }

        return $rules;
    }

    public function defaultRules()
    {
        return [];
    }

    // get stored value -- convert db value to application value
    public function getValue($value)
    {
        return $value;
    }

    // set stored value -- convert application value to db value
    public function setValue($value)
    {
        return $value;
    }

    /**
     * Helper method that generates a query filter based
     */
    public function generateFilter($model, $attribute, $params)
    {
        switch ($this->filterType) {
            case '=':
                return [$attribute => $model->$attribute];
                break;
            case 'LIKE':
                return [$attribute => ['like' => $model->$attribute]];
                break;
        }
    }

}