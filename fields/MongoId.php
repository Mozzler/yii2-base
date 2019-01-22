<?php
namespace mozzler\base\fields;

class MongoId extends Base {
	
	public $type = 'MongoId';
	
	// get stored value -- convert db value to application value
	public function getValue($value) {
		return (string)$value;
	}
	
	// set stored value -- convert application value to db value
	public function setValue($value) {
		if (!$value)
			return null;
			
		return new \MongoDB\BSON\ObjectId($value);
	}
	
	public function generateFilter($model, $attribute) {
    	return [$attribute => new \MongoDB\BSON\ObjectId($model->$attribute)];
    }
	
}

?>
