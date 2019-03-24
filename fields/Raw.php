<?php
namespace mozzler\base\fields;

/**
 * Class Raw
 * @package mozzler\base\fields
 *
 * This is used for fields that have no validation.
 * For example the auditLog previousValue and newValue fields which can take any type of object.
 */
class Raw extends Base {
	
	public $type = 'Raw';
	public $operator = "=";
	
	public function defaultRules() {return [];}
	
}
