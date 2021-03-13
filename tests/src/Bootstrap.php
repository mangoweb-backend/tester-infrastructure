<?php declare(strict_types = 1);

namespace MangowebTests\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\MangoTesterExtension;
use Nette\Configurator;

class Bootstrap
{
	public const FACTORY = [self::class, 'createContainer'];

	public static function createContainer()
	{
		$configurator = new Configurator();
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->enableDebugger(__DIR__ . '/../temp');
		$configurator->addConfig([
			'extensions' => [
				MangoTesterExtension::class,
			],
			'services' => [
				AppConfiguratorFactory::class,
			]
		]);

		return $configurator->createContainer();
	}
}
