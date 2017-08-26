<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\NextrasDbal;

use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Nette\DI\Statement;
use Nextras\Dbal\Connection;


class NextrasDbalHook implements IAppContainerHook
{

	public function onConfigure(Configurator $configurator): void
	{

	}


	public function onCompile(ContainerBuilder $builder): void
	{
		$def = $builder->getDefinitionByType(Connection::class);
		$factory = $def->getFactory();
		assert($factory !== NULL);
		$args = $factory->arguments;
		$args['config'] = new Statement('array_merge(?, ?)', [
			$args['config'],
			[
				'database' => new Statement('@databaseCreator::getDatabaseName'),
			],
		]);
		$def->setArguments($args);
		$def->addSetup(['@databaseCreator', 'createTestDatabase']);
	}


	public function onCreate(Container $applicationContainer): void
	{

	}

}
