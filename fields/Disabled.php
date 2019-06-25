<?php

namespace mozzler\base\fields;

/**
 * Class Disabled
 * @package mozzler\base\fields
 *
 * A disabled field is one where the input is set to disabled
 * This prevents users from editing it.
 *
 * It's useful if you have a default value and you want users to see it, but not be able to set it
 *
 * Note: There's no server side validation of the disabled field.
 * If the disabled attribute is removed and users edit it, that'll be accepted
 */
class Disabled extends Base
{

    public $type = 'Disabled';
    public $filterType = "LIKE";

    public function defaultRules()
    {
        return [];
    }

}
