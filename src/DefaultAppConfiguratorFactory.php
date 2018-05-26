<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\Container\IAppConfiguratorFactory;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\Extensions\ExtensionsExtension;


class DefaultAppConfiguratorFactory implements IAppConfiguratorFactory
{
	private const COPIED_PARAMETERS = [
		'logDir',
		'tempDir',
		'appDir',
		'wwwDir',
		'consoleMode',
	];

	/** @var array */
	private $configFiles;

	/** @var array */
	private $copiedParameters;

	/** @var bool */
	private $defaultExtensionsOverride = true;


	public function __construct(array $configFiles, array $copiedParameters = self::COPIED_PARAMETERS)
	{
		$this->configFiles = $configFiles;
		$this->copiedParameters = $copiedParameters;
	}


	public function disableDefaultExtensionsOverride(bool $disable = true): void
	{
		$this->defaultExtensionsOverride = !$disable;
	}


	public function create(Container $testContainer): Configurator
	{
		$params = $testContainer->getParameters();

		$configurator = new Configurator;
		if ($this->defaultExtensionsOverride) {
			$configurator->defaultExtensions = [
				'extensions' => ExtensionsExtension::class,
			];
		}

		$configurator->setDebugMode(true);
		$configurator->setTempDirectory($params['tempDir']);

		$parameters = array_intersect_key($params, array_fill_keys($this->copiedParameters, true));

		$configurator->addParameters($parameters);
		foreach ($this->configFiles as $file) {
			$configurator->addConfig($file);
		}

		return $configurator;
	}
}
