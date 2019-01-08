<?php
namespace mozzler\base\fields;

class RelateOne extends MongoId {
	
	public $type = 'RelateOne';
	
	/**
	 * What model is this relationship linked to?
	 */
	public $relatedModel;
	
	/**
	 * What is the foreign key field for this relationship?
	 */
	public $relatedField = '_id';
	
	public $linkField = '_id';
	
}

?>
