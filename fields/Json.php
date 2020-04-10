<?php

namespace mozzler\base\fields;

class Json extends Base
{

    public $type = 'Json';
    public $filterType = "LIKE";

    /**
     * @param $value array|string an existing array or JSON string
     * @return array|mixed
     *
     * Automatically converts a JSON string to an actual array
     */
    public function setValue($value)
    {
        if (empty($value)) {
            return $value;
        }
        if (is_string($value)) {
            $jsonDecoded = json_decode($value, true);
            if (is_array($jsonDecoded)) {
                $value = $jsonDecoded;
            }
        }
        return $value;
    }
}
