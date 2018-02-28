<?php declare(strict_types = 1);

namespace MangoShopTests;

use Mangoweb\Tester\DatabaseCreator\DatabaseCreator;
use Mangoweb\Tester\DatabaseCreator\IDatabaseNameResolver;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\StaticClass;

class NextrasDbalServiceHelpers
{
	use StaticClass;

	public static function modifyConnectionDefinition(ServiceDefinition $definition)
	{
		$factory = $definition->getFactory();
		assert($factory !== null);
		$args = $factory->arguments;
		$args['config'] = new Statement('array_merge(?, ?)', [
			$args['config'],
			[
				'database' => new Statement('@databaseCreator::getDatabaseName'),
			],
		]);
		$definition->setArguments($args);
		$definition->addSetup(['@databaseCreator', 'createTestDatabase']);
	}
}
