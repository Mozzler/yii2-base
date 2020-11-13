<?php
namespace mozzler\base\fields;

class Json extends Base
{
    public function defaultRules()
    {
        return [
            // -- A standalone validator
            \mozzler\base\validators\JsonValidator::className()
        ];
    }

    public $type = 'Json';
    public $filterType = "LIKE";

}
