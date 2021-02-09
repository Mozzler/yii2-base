<?php

namespace mozzler\base\fields;

/**
 * Class Phone
 *
 * Home, Mobile or International phone numbers
 * @package mozzler\base\fields
 */
class Phone extends Base
{

    public $type = 'Phone';
    public $filterType = "LIKE";

    public function defaultRules()
    {
        return [
            'string' => ['min' => 3, 'max' => 20], // Allow for special phone numbers like 136633 or long International calling numbers e.g +61433483008 and maybe directory numbers
            'filter' => ['filter' => 'trim'],
            'mozzler\base\validators\PhoneValidator' => ['country' => 'AU'] // Note: You might need to change the country if not saving for Australia check validators/PhoneValidator.php for more info
        ];
    }

}
