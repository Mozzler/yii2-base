<?php
namespace mozzler\base\fields;

class Postcode extends Base {
	
	public $type = 'Postcode';
	public $filterType = "LIKE";

	// We can't use integer as we want to save a postcode like 0001
    public function defaultRules() {
        return [
            'string' => ['min' => 4, 'max' => 4], // Assuming Australian based 4 digit postcodes, not sure if there's longer or shorter ones
            'match' => ['pattern' => '/[0-9]{4}/i'], // Only allow numbers
            'filter' => ['filter' => 'trim'],
        ];
    }
}
