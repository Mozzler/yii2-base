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
		
		foreach ($existingIndexes as $eIndex) {
			// Get the existingIndex Name
			$indexName = $eIndex['name'];
			
			// Always skip the _id index by default this is index 
			// amade by mongo nd we don't want to mess with it
			if ($indexName == "_id_") {
				continue;
			}

			// Check if existingIndex is present or changed in modelIndexes
			if (isset($modelIndexes[$indexName])) {
				if ($eIndex['key'] == $modelIndexes[$indexName]['columns']) {
					if (ArrayHelper::getValue($eIndex, 'unique') != $modelIndexes[$indexName]['options']['unique']) {
						$this->handleUpdate($collection, $indexName, $modelIndexes[$indexName]);
					} 
				} else {
					$this->handleUpdate($collection, $indexName, $modelIndexes[$indexName]);
				}
			} else {
				$this->handleDelete($collection, $indexName);
			}

			
    	}
        
        $this->handleCreate($collection, $modelIndexes, $existingIndexes);
	}
	
	/**
     * Handle updating existing indexes if they have changed
     */
	protected function handleUpdate($collection, $indexName, $indexConfig)
	{
		$collection->dropIndexes($indexName);

		$options = $indexConfig['options'];
		$options['name'] = $indexName;

		$collection->createIndex($indexConfig['columns'], $options);
		
        $this->addLog("Updated index: ".$indexName);
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
		$collection->dropIndexes($indexName);
		
        $this->addLog("Deleted index: ".$indexName);
	}
	
	protected function getExistingIndexes($className)
	{
    	$collection = $this->getCollection($className);
		$indexes = $collection->listIndexes();
		
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