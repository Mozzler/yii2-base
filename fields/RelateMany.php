<?php
namespace mozzler\base\fields;

class RelateMany extends RelateOne {
	
	public $type = 'RelateMany';
	
	public $relationDefaults = [
		'filter' => [],
		'limit' => 20,
		'offset' => null,
		'orderBy' => [],
		'fields' => null,
		'checkPermissions' => true
	];
	
}

?>
