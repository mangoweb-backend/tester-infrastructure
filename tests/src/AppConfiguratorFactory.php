<?php declare(strict_types = 1);

namespace MangowebTests\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\Container\IAppConfiguratorFactory;
use Nette\Configurator;
use Nette\DI\Container;

class AppConfiguratorFactory implements IAppConfiguratorFactory
{
	public function create(Container $testContainer): Configurator
	{
		$configurator = new Configurator();
		$configurator->setTempDirectory($testContainer->getParameters()['tempDir']);
		$configurator->addConfig([
			'services' => [
				\DateTimeImmutable::class,
			],
		]);

		return $configurator;
	}
}
