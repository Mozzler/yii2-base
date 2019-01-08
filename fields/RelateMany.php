<?php
namespace mozzler\base\fields;

class RelateMany extends MongoId {
	
	public $type = 'RelateMany';
	
	/**
	 * What model is this relationship linked to?
	 */
	public $relatedModel;
	
	/**
	 * What is the foreign key field for this relationship?
	 */
	public $relatedModelField = '_id';
	
}

?>
