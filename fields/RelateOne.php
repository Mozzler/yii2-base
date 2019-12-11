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
	
	/**
	 * If we have a dynamic field, we can specify which attribute
	 * on the model defines the relatedModel classname.
	 * 
	 * eg: If `$relateModelField` = parentType, then
	 * `$model->parentType` is the classname of the related model
	 *
	 * eg: app\models\User
	 */
	public $relatedModelField;

	public $linkField = '_id';
	
}
