<?php

namespace mozzler\base\fields;

use yii\helpers\VarDumper;

class RelateMany extends RelateOne
{

    public $type = 'RelateMany';

    public $relationDefaults = [
        'filter' => [],
        'limit' => 20,
        'offset' => null,
        'orderBy' => [],
        'fields' => null,
        'checkPermissions' => true
    ];

    /**
     * @param $value
     * @return mixed
     *
     * Example input:
     *  [
     * '5fc8d8701358ad7f960875f4',
     * '5fc8d8701358ad7f960875f6',
     * '5fc89ba9a232c55c5e076204',
     * ]
     *
     * We want to convert those strings to MongoDB ObjectIDs
     */
    public function setValue($value)
    {
        \Yii::debug("Setting the relateMany field based on the input value: " . VarDumper::export($value));
        if (is_string($value)) {
            $value = json_decode($value, true);
            if ('' == $value) {
                $value = []; // Empty string = empty array
            }
        }
        $convertedValue = [];
        foreach ($value as $inputString) {
            $convertedValue[] = self::convertToObjectIdIfPossible($inputString);
        }
        return $convertedValue;
    }

    protected static function convertToObjectIdIfPossible($inputString)
    {
        try {
            return new \MongoDB\BSON\ObjectId($inputString);
        } catch (\Throwable $exception) {
            \Yii::error("Error with the RelateMany input {$inputString} is can't be converted to a valid ObjectId: " . \Yii::$app->t::returnExceptionAsString($exception));
            return $inputString;
        }
    }

    // get stored value -- convert db value to application value
    public function getValue($value)
    {
        if (is_string($value)) {
            return $value;
        }
        return json_encode($value);
    }

//    // set stored value -- convert application value to db value
//    public function setValue($value) {
//        if (!$value)
//            return null;
//
//        try {
//            return new \MongoDB\BSON\ObjectId($value);
//        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
//            if ($this->allowUserDefined) {
//                return $value;
//            }
//
//            throw $e;
//        }
//    }

//    public function generateFilter($model, $attribute, $params)
//    {
//        return [$attribute => [new \MongoDB\BSON\ObjectId($model->$attribute)];
//    }

}

