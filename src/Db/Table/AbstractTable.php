<?php

namespace ProDevZone\Db\Table;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Adapter\AdapterAwareInterface;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use \ProDevZone\Db\Model\ModelInterface;

abstract class AbstractTable extends AbstractTableGateway
	implements AdapterAwareInterface, ServiceLocatorAwareInterface
{
	protected $table;

	/**
	 * @var $hydrator \ProDevZone\Db\Hydrator\AbstractHydrator
	 */
	protected $hydrator;

	/**
	 * @var \ProDevZone\Db\Model\ModelInterface
	 */
	protected $model;

	/**
	 * @var \Zend\ServiceManager\ServiceManager
	 */
	protected $sm;

	private $pk = 'id';

	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->sm = $serviceLocator;
	}

	public function getServiceLocator()
	{
		return $this->sm;
	}

	public function setDbAdapter(Adapter $adapter)
	{
		$this->adapter = $adapter;
		$this->resultSetPrototype = new HydratingResultSet();

		$this->initialize();
	}

	public function find($where = null)
	{
		$where = $this->prepareWhere($where);
		$sql = $this->sql->select()->where($where);

		return $this->fetchAll($sql);
	}

	public function findFirst($where = null)
	{
		$where = $this->prepareWhere($where);
		$sql = $this->sql->select()->where($where)->limit(1);

		return $this->fetchAll($sql);
	}

	public function save($data)
	{
		if ($data instanceof ModelInterface) {
			/* @var $hydrator \ProDevZone\Db\Hydrator\AbstractHydrator */
			$hydrator = $this->sm->get($this->hydrator);

			$data = $hydrator->extract($data);
		}

		if (is_array($data)) {
			if (isset($data[$this->pk]) && (int)$data[$this->pk] != 0) {
				$this->update($data, array($this->pk => $data[$this->pk]));
				$result = $data[$this->pk];
			} else {
				$this->insert($data);
				$result = $this->getLastInsertValue();
			}
		} else {
			throw new \Exception(
				'Wrong data to save ' . $this->model . ' object!'
			);
		}

		return $result;
	}

	public function remove($data)
	{
		if ($data instanceof ModelInterface) {
			/* @var $hydrator \ProDevZone\Db\Hydrator\AbstractHydrator */
			$hydrator = $this->sm->get($this->hydrator);

			$data = $hydrator->extract($data);
		}

		if (is_int($data) && (int)$data != 0) {
			$id = $data;
		} else if (is_array($data) && isset($data[$this->pk]) && (int)$data[$this->pk] != 0) {
			$id = $data[$this->pk];
		} else {
			throw new \Exception(
				'Wrong data to save ' . $this->model . ' object!'
			);
		}

		return $this->delete(array($this->pk => $id));
	}

	private function fetchAll($sql)
	{
		/* @var $hydrator \ProDevZone\Db\Hydrator\AbstractHydrator */
		$hydrator = $this->sm->get($this->hydrator);

		/* @var \ProDevZone\Db\Model\ModelInterface */
		$model = $this->sm->get($this->model);

		$result = array();
		foreach ($this->selectWith($sql)->toArray() as $row) {
			$result[] = $hydrator->hydrate($row, clone $model);
		}

		return $result;
	}

	private function prepareWhere($where)
	{
		$condition = array();
		if (is_int($where) && (int)$where != 0) {
			$condition[$this->pk] = $where;
		} else if (is_array($where)) {
			$condition = $where;
		} else {
			$condition = array();
		}

		return $condition;
	}

	protected function getAliases($columns, $table)
	{
		$result = [];
		foreach ($columns as $column) {
			$result[$table . '_' . $column] = $column;
		}

		return $result;
	}
}