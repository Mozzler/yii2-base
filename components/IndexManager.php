<?php
namespace mozzler\base\components;

use yii\helpers\ArrayHelper;

class IndexManager
{
    
    public $logs = [];
	
	public function syncModelIndexes($className) {
    	$modelIndexes = $this->getModelIndexes($className);
    	$existingIndexes = $this->getExistingIndexes($className);
        
		$collection = $this->getCollection($className);
		
		foreach ($existingIndexes as $eIndex){
			// Get the existingIndex Name
			$indexName = array_keys($eIndex['key'])[0];
			
			// Always skip the _id index by default this is index 
			// amade by mongo nd we don't want to mess with it
			if ($indexName == "_id") {
				continue;
			}

			// Check if existingIndex is present or changed in modelIndexes
			$existsFlag = false;
			$changedFlag = false;
			foreach ($modelIndexes as $mIndex => $mIndexConfig) {
				if ($eIndex['key'] == $mIndexConfig['columns']) {
					$existsFlag = true;

					if (!isset($eIndex['unique']) || ArrayHelper::getValue($eIndex, 'unique') != $mIndexConfig['options']['unique']) {
						$changedFlag = true;
						break;
					}
				}
			}

			if ($existsFlag && $changedFlag) {
				// if index  exists and with changes UPDATE the index
				$this->handleUpdate($collection, $eIndex['key'], $mIndexConfig);
			} elseif (!$existsFlag) {
				// if index does not exists, DELETE it from collection
				$this->handleDelete($collection, $eIndex['key']);
			}
			
    	}
        
        $this->handleCreate($collection, $modelIndexes, $existingIndexes);
	}
	
	/**
     * Handle updating existing indexes if they have changed
     */
	protected function handleUpdate($collection, $indexName, $indexConfig)
	{
        $collection->dropIndex($indexName);
		$collection->createIndex($indexConfig['columns'], $indexConfig['options']);
		
        $this->addLog("Updated index: ".array_keys($indexName)[0]);
	}
	
	/**
     * Handle creating new indexes if they don't exist
     */
	protected function handleCreate($collection, $modelIndexes, $existingIndexes)
	{
    	foreach ($modelIndexes as $indexName => $indexConfig) {
        	if (!isset($existingIndexes[$indexName])) {
            	try {
					$options = $indexConfig['options'];
					$options['name'] = $indexName;

                	if ($collection->createIndex($indexConfig['columns'], $options)) {
                    	$this->addLog("Creating index: $indexName");
                	} else {
                    	$this->addLog("Unable to create index: $indexName", 'error');
                	}
                } catch (\Exception $e) {
                    $this->addLog("Exception creating: $indexName (".$e->getMessage().")", 'error');
                }
        	}
    	}
	}
	
	/**
     * Handle deleting existing indexes if they have been removed
     */
	protected function handleDelete($collection, $indexName)
	{
		$collection->dropIndex($indexName);
		
        $this->addLog("Deleted index: ".array_keys($indexName)[0]);
	}
	
	protected function getExistingIndexes($className)
	{
    	$collection = $this->getCollection($className);
		$indexes = $collection->listIndexes();
		// $this->addLog(' existing indexes ---> ' . json_encode($indexes));
		
		return $indexes;
	}
	
	protected function getModelIndexes($className)
	{
    	return $className::modelIndexes();
	}
	
	protected function getCollection($className) {
    	return $className::getCollection();
	}
	
	protected function addLog($message, $type='info') {
    	$this->logs[] = [
        	'message' => $message,
        	'type' => $type
    	];
	}
	
}