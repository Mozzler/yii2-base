<?php

namespace mozzler\base\fields;

use mozzler\base\validators\JsonValidator;

/**
 * Class JsonArray
 * @package mozzler\base\fields
 *
 * A field that converts JSON into PHP / Mongo native arrays on save
 *
 * Use the Json field type if you want to save the actual JSON string.
 * Use this JsonArray field type if you want to save JSON as an array. Or just directly save as an array
 */
class JsonArray extends Base
{

    public $type = 'JsonArray';
    public $filterType = "LIKE";


    public function defaultRules()
    {
        return [
            // -- A standalone validator
            \mozzler\base\validators\JsonValidator::className()
        ];
    }


    /**
     * @param $value
     * @return mixed
     *
     * Convert a JSON value into an array if possible
     *
     * Otherwise it's expected you'll provide a full Array anyway
     */
    public function setValue($value)
    {
        if (is_string($value)) {
            $jsonDecoded = json_decode($value, true);
            if (is_array($jsonDecoded)) {
                return $jsonDecoded;
            }
            if ('' == $value) {
                return []; // Empty string = empty array
            }
        }
        return parent::setValue($value);
    }
}
