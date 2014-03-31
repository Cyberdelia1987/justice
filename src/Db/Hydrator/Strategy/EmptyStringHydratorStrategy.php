<?php
/**
 * @author Сибов Александр<sib@avantajprim.com>
 */

namespace ProDevZone\Db\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;

class EmptyStringHydratorStrategy implements StrategyInterface
{
	public function extract($value)
	{
		if (!is_string($value) || empty($value)) {
			$value = 'NULL';
		}

		return $value;
	}

	public function hydrate($value)
	{
		if (!is_string($value) || empty($value)) {
			return 'NULL';
		}

		return $value;
	}
}