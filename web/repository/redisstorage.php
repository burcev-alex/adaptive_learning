<?php
namespace Web\Repository;

/**
 * Class RedisStorage
 * @package App\Web\Repository
 */
class RedisStorage implements Storage
{
	/**
	 * @param $key
	 * @param $object
	 * @param string $cacheId
	 *
	 * @return int|mixed|string
	 */
	public function save($key, $object, $cacheId = ""){
		if(strlen($cacheId) == 0) {
			$cacheId = microtime(true);
		}

		try {
			$redis = $this->entity();

			if(is_array($object)) {
				$content = base64_encode(serialize($object));
			}
			else{
				$content = base64_encode($object);
			}

			$redis->hset($key, $cacheId, $content);
		}
		catch (\Exception $e){
			$cacheId = 0;
		}

		return $cacheId;
	}

	/**
	 * @param $key
	 *
	 * @return array
	 */
	public function getAll($key){
		$result = [];

		$redis = $this->entity();
		$resource = $redis->hgetall($key);

		foreach ($resource as $k=>$item) {
			$strings = base64_decode($item);
			if(substr_count($strings, "{") > 0) {
				$result[$k] = unserialize($strings);
			}
			else{
				$result[$k] = $strings;
			}
		}

		return $result;
	}

	/**
	 * @param $key
	 * @param $cacheId
	 *
	 * @return array
	 */
	public function findById($key, $cacheId){
		$redis = $this->entity();
		$result = $redis->hget($key, $cacheId);

		$strings = base64_decode($result);
		if(substr_count($strings, "{") > 0) {
			$result = unserialize($strings);
		}
		else{
			$result = $strings;
		}

		return $result;
	}

	/**
	 * @param $key
	 * @param $id
	 *
	 * @return bool
	 */
	public function remove($key, $id){
		$redis = $this->entity();
		$redis->hdel($key, $id);

		return true;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function clear($key)
	{
		$redis = $this->entity();
		$redis->del($key, 'to delete');

		return true;
	}

	/**
	 * @return Predis\Client
	 */
	private function entity(){
		$redis = new \Predis\Client(
			array(
				"scheme" => "tcp",
				"host" => '127.0.0.1',
				"port" => 6379,
				"read_write_timeout" => 0
			)
		);

		return $redis;
	}
}