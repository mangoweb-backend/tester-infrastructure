<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\Database;

use Mangoweb\Tester\DatabaseCreator\DatabaseCreator;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class DatabaseCreatorHook implements IAppContainerHook
{

	/** @var DatabaseCreator */
	private $databaseCreator;


	public function __construct(DatabaseCreator $databaseCreator)
	{
		$this->databaseCreator = $databaseCreator;
	}


	public function onConfigure(Configurator $configurator): void
	{

	}


	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->addDefinition('databaseCreator')
			->setClass(DatabaseCreator::class)
			->setDynamic();
		$builder->prepareClassList();
	}


	public function onCreate(Container $applicationContainer): void
	{
		$applicationContainer->addService('databaseCreator', $this->databaseCreator);
	}

}
