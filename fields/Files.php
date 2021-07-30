<?php

namespace mozzler\base\fields;

/**
 * Class Files
 * @package mozzler\base\fields
 *
 * An array of files, for adding many files
 */
class Files extends JsonArray
{

    public $type = 'Files';
    /**
     * What model is this relationship linked to?
     */
    public $relatedModel = \mozzler\base\models\File::class; // The Mozzler base File model
    public $filesystemName = 'fs'; // Default to using \Yii::$app->fs but allow this to be something which can be modified
    /**
     * What is the foreign key field for this relationship?
     */
    public $relatedField = '_id';

    // -- Custom settings which allow you to override the ones used by models/behaviors/FileUploadBehaviour.php
    public $filenameTwigTemplate;
    public $folderpathTwigTemplate;
    // convertFunction is a function which can convert the file to a new type (e.g PNG to resized JPG)
    // The function will be given the $fileInfo and the $file object and expects the $fileInfo returned
    // Accepts a closure e.g: function($fileInfo, $file) { /* Do stuff...*/ return $fileInfo;}
    // Also accepts a string pointing to a method on the model e.g: 'avatarFileConvert'
    // Or accepts the array style callable e.g: '/class/Name', 'methodName']
    public $convertFunction;

    public function setValue($value)
    {

        // ensureId doesn't like working on an empty field
        if (empty($value)) {
            return $value;
        }
        // Convert to array
        $value = parent::setValue($value);

        // Ensure the ObjectId's are converted as such
        if (is_array($value)) {

            $value = array_map(function ($item) {
                try {
                    if (is_string($item)) {
                        return \Yii::$app->t::ensureId($item);
                    }
                } catch (\Throwable $exception) {
                    \Yii::error(\Yii::$app->t::returnExceptionAsString($exception));
                    return $item;
                }
                return $item;
            }, $value);
        }
        return $value;
    }


//    /**
//     * Updating the required whenclient to work with the Filepond style hidden input
//     * format: [validator, parameter => value]
//     */
//    public function rules()
//    {
//        $rules = parent::rules();
//        if ($this->required && isset($rules['required'])) {
//            $rules['required']['whenClient'] = "function (attribute, value) { return \"\" == $('input[name=\"{$this->model->formName()}[$this->attribute]\"]').val(); }"; // Hacking the whenClient to work with the Filepond style hidden input
//        }
//
//        return $rules;
//    }

}
