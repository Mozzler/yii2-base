<?php
namespace mozzler\base\components;
use yii\mongodb\Cache as MongoCache;

class MozzlerCache extends MongoCache {

	public $namespace;
	
	// prefix key with namespace
	public function buildKey($key) {
		$defaultKey = parent::buildKey($key);
		return $this->namespace.'/'.$defaultKey;
	}
	
	public function clear($namespace) {
		$this->db->getCollection($this->cacheCollection)
			->remove(["LIKE", "id", $namespace."/"]);
	}
	
	public function ensureIndexes() {
		$this->db->getCollection($this->cacheCollection)
			->createIndex(["id" => 1, "expire" => 1], ["unique" => 1]);
	}
	
}

?>