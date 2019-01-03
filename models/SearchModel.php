<?php
namespace mozzler\base\models;

use \yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\data\ActiveDataFilter;

class SearchModel extends DynamicModel {
	
	protected $parentModel;
	
	/**
	 * Build attributes for this dynamic model from the parent model
	 */
	public function __construct($parentModel) {
		$attributes = $parentModel->activeAttributes();
		$this->parentModel = $parentModel;
		\Yii::trace('set parent');
		return parent::__construct($attributes, []);
	}
	
	public function init() {
		\Yii::trace('init start');
		parent::init();
		
		\Yii::trace('parent init done');
		
		// Add fields to this search model based on parentModel activeAttributes()
		foreach ($this->parentModel->activeAttributes() as $attribute) {
			$modelField = $this->parentModel->getModelField($attribute);
			$modelField->applySearchRules($this);
		}
		
		\Yii::trace('init done');
	}
	
	/**
	 * Build a DataProvider that has a query filtering by the
	 * data provided in $params
	 */
	public function search($params=[]) {
		// create a query from the parent model
		$query = $this->parentModel::find();
		
		// load the parameters into this model and continue
		// if the model validates
		if ($this->load($params)) {
			// iterate through the search attributes building a generic filter array
			$filterParams = ['and' => []];
			$attributeFilters = [];
			foreach ($this->attributes() as $attribute) {
				if ($this->$attribute) {
					$attributeFilters[] = [$attribute => $this->$attribute];
				}
			}
			
			// if we have filters to apply, build a filter condition that can
			// be added to the query
			if (sizeof($attributeFilters) > 0) {
				$filterParams['and'] = $attributeFilters;
			
				$params = ['filter' => $filterParams];
				$dataFilter = new ActiveDataFilter([
					'searchModel' => $this
				]);
				$filterCondition = null;
		        if ($dataFilter->load($params)) {
		            $filterCondition = $dataFilter->build();
		        }
				
				// if we have a valid filter condition, add it to the query
				if ($filterCondition !== null) {
					$query->andWhere($filterCondition);
				}
			}
		}
		
		return new ActiveDataProvider([
			'query' => $query,
		]);
    }
	
}


?>