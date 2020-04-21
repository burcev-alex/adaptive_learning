<?php
namespace Web\Repository;

interface Storage
{
	public function save($key, $object);
	public function remove($key, $object);
	public function findById($key, $id);
	public function getAll($key);
	public function clear($key);
}