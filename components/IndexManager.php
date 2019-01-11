<?php
namespace mozzler\base\components;

class IndexManager
{
    
    public $logs = [];
	
	public function syncModelIndexes($className) {
    	$modelIndexes = $this->getModelIndexes($className);
    	$existingIndexes = $this->getExistingIndexes($className);
        
        $collection = $this->getCollection($className);
        
        $this->handleUpdate($collection, $modelIndexes, $existingIndexes);
        $this->handleCreate($collection, $modelIndexes, $existingIndexes);
        $this->handleDelete($collection, $modelIndexes, $existingIndexes);
	}
	
	/**
     * Handle updating existing indexes if they have changed
     */
	protected function handleUpdate($collection, $modelIndexes, $existingIndexes)
	{
	}
	
	/**
     * Handle creating new indexes if they don't exist
     */
	protected function handleCreate($collection, $modelIndexes, $existingIndexes)
	{
    	foreach ($modelIndexes as $indexName => $indexConfig) {
        	if (!isset($existingIndexes[$modelIndexes])) {
            	try {
                	if ($collection->createIndex($indexConfig['columns'], $indexConfig['options'])) {
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
	protected function handleDelete($collection, $modelIndexes, $existingIndexes)
	{
    	
	}
	
	protected function getExistingIndexes($className)
	{
    	$collection = $this->getCollection($className);
    	$indexes = $collection->listIndexes();
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