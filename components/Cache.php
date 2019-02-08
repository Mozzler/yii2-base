<?php
namespace mozzler\base\components;
use yii\base\Component;

/**
 * Cache management for applications
 *
 * The same cache class is used for the request cache and the common cache.
 *
 * **Request cache vs Common cache:**
 *
 * The request cache is unique for the current web request only, whereas the
 * common cache is persistent across all web requests and is associated with
 * a deployment.
 *
 * **Namespace:**
 *
 * All cache methods require a namespace. By convention, use a namespace
 * associated with the package the code being executed sits within.
 *
 * Namespaces are also useful if you want to easily clear a group of
 * cache values. For example, you may specify a namespace `mozzler.auth/stats`
 * and then clear multiple cache keys at once (using `cache.clear("mozzler.authstats")`).
 *
 * Usage example:
 *
 * ```
 *
 * // Set a cache value
 * \Yii::$app->cache->set("mozzler.auth", "activeUserCount", 122);
 *
 * // Get a cache value
 * var activeUserCount = \Yii::$app->cache->get("mozzler.auth", "activeUserCount");
 *
 * // Clear a cache value
 * \Yii::$app->cache->delete("mozzler.auth", "activeUserCount");
 *
 * // Delete all cache entries for a namespace
 * \Yii::$app->cache->clear("mozzler.auth");
 * ```
 *
 * @see \mozzler\base\components\MozzlerCache
 */
class Cache extends Component {

	/**
	 * @ignore
	 */
	public $cacheCollection;

	/**
	 * @ignore
	 */
	protected $cache;

	/**
	 * @ignore
	 */
	public function init() {
		$this->cache = new MozzlerCache([
			'cacheCollection' => $this->cacheCollection
		]);
        $this->ensureIndexes();
        $this->ensureRbacDisabled();
	}

	public function get($namespace, $key) {
		$this->cache->namespace = $namespace;
		return $this->cache->get($key);
	}

	public function exists($namespace, $key) {
		$this->cache->namespace = $namespace;
		return $this->cache->exists($key);
	}

	/**
	 * @ignore
	 */
	public function mget($namespace, $keys) {
		$this->cache->namespace = $namespace;
		return $this->cache->mget($keys);
	}

	public function set($namespace, $key, $value, $duration = 0, $dependency = null) {
		$this->cache->namespace = $namespace;
		return $this->cache->set($key, $value, $duration, $dependency);
	}

	/**
	 * @ignore
	 */
	public function mset($namespace, $items, $duration = 0, $dependency = null) {
		$this->cache->namespace = $namespace;
		return $this->cache->mset($items, $duration, $dependency);
	}

	/**
	 * @ignore
	 */
	public function madd($namespace, $items, $duration = 0, $dependency = null) {
		$this->cache->namespace = $namespace;
		return $this->cache->madd($items, $duration, $dependency);
	}

	public function add($namespace, $key, $value, $duration = 0, $dependency = null) {
		$this->cache->namespace = $namespace;
		return $this->cache->add($key, $value, $duration, $dependency);
	}

	public function delete($namespace, $key) {
		$this->cache->namespace = $namespace;
		return $this->cache->delete($key);
	}

	/**
	 * Clear all cache data from a specific namespace
	 */
	public function clear($namespace) {
		$this->cache->clear($namespace);
	}

	/**
	 * @ignore
	 * @deprecated
	 */
	public function ensureIndexes() {
		$this->cache->ensureIndexes();
	}

    /**
     * If RBAC is enabled, ignore the app.cache collection (especially as the model doesn't exist)
     */
    private function ensureRbacDisabled() {
        if (isset(\Yii::$app->rbac)) {
            \Yii::$app->rbac->ignoreCollection($this->cacheCollection);
        }
    }

}