<?php

namespace mozzler\base\fields;

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
     * [
     *  '5fc8d8701358ad7f960875f4',
     *  '5fc8d8701358ad7f960875f6',
     *  '5fc89ba9a232c55c5e076204',
     * ]
     *
     * We DO NOT want to convert those strings to MongoDB ObjectIDs
     * because otherwise the select2 widget complains.
     * Weirdly the error you get is "Array to string conversion" in the /app/vendor/yiisoft/yii2/helpers/BaseHtml.php at line 1867
     * But the issue only appears if the value is an array of ObjectIDs but is fine if it's an array of strings
     */
    public function setValue($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
            if ('' == $value) {
                $value = []; // Empty string = empty array
            }
        }
        return $value;
    }


    // get stored value -- convert db value to application value
    public function getValue($value)
    {
        return $value;
    }

}

