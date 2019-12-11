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
        return \Yii::$app->t::ensureId($value);
    }
}
