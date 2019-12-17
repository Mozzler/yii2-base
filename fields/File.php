<?php

namespace mozzler\base\fields;

class File extends Base
{

    public $type = 'File';
    /**
     * What model is this relationship linked to?
     */
    public $relatedModel = \mozzler\base\models\File::class; // The Mozzler base File model

    /**
     * What is the foreign key field for this relationship?
     */
    public $relatedField = '_id';

    public function setValue($value)
    {
        // ensureId doesn't like working on an empty field
        if (empty($value)) {
            return $value;
        }
        return \Yii::$app->t::ensureId($value);
    }


    /**
     * Updating the required whenclient to work with the Filepond style hidden input
     * format: [validator, parameter => value]
     */
    public function rules()
    {
        $rules = parent::rules();
        if ($this->required && isset($rules['required'])) {
            $rules['required']['whenClient'] = "function (attribute, value) { return \"\" == $('input[name=\"{$this->model->formName()}[$this->attribute]\"]').val(); }"; // Hacking the whenClient to work with the Filepond style hidden input
        }

        return $rules;
    }

}
