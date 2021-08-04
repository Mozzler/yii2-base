<?php

namespace mozzler\base\fields;

class File extends Base
{

    public $type = 'File';
    /**
     * What model is this relationship linked to?
     */
    public $relatedModel = \mozzler\base\models\File::class; // The Mozzler base File model
    public $filesystemName = 'fs'; // Default to using \Yii::$app->fs but allow this to be something which can be modified
    /**
     * What is the foreign key field for this relationship?
     */
    public $relatedField = '_id';


    // Filepond Core Config Settings
    // NB: Also update the Files Field
    public $filePondConfig = [
        // Core properties - Explained in https://pqina.nl/filepond/docs/api/instance/properties/
        'allowMultiple' => false, // We don't currently allow an array of Files to be saved, but that can be made to happen
        // ---
        'allowDrop' => true, // Enable or disable drag n' drop
        'allowBrowse' => true, // Enable or disable file browser
        'allowPaste' => true, // Enable or disable pasting of files. Pasting files is not supported on all browesrs.
        'allowReplace' => true, // Allow drop to replace a file, only works when allowMultiple is false
        'allowRevert' => true, // Enable or disable the revert processing button
        'allowRemove' => true, // When set to false the remove button is hidden and disabled
        'allowProcess' => true, // Enable or disable the process button
        'allowReorder' => false, // Allow users to reorder files with drag and drop interaction. Note that this only works in single column mode. It also only works on browsers that support pointer events.
        'className' => null, // Additional CSS class to add to the root element
        'required' => false, // Sets the required attribute to the output field
        'disabled' => false, // Sets the disabled attribute to the output field
    ];


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
        try {
            return \Yii::$app->t::ensureId($value);
        } catch (\Throwable $exception) {
            \Yii::error(\Yii::$app->t::returnExceptionAsString($exception));
            return $value;
        }
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
