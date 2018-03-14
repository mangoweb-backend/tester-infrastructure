<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Nette;
use Nette\DI;


class InfrastructureConfigurator
{
	/** @var array */
	protected $parameters;

	/** @var array */
	protected $configs = [];


	public function __construct(string $path)
	{
		$this->parameters = [
			'debugMode' => true,
			'productionMode' => false,
			'consoleMode' => false,
			'tempDir' => $path,
			'appDir' => $path . '/dummyAppDir',
			'wwwDir' => $path . '/dummyWwwDir',
		];
	}


	public function setupTester(): void
	{
		\Tester\Environment::setup();
		\Tester\Dumper::$maxPathSegments = 0;
	}


	public function setTimeZone(string $timezone): void
	{
		date_default_timezone_set($timezone);
		@ini_set('date.timezone', $timezone); // @ - function may be disabled
	}


	public function addParameters(array $params): void
	{
		$parameters = DI\Config\Helpers::merge($params, $this->parameters);
		assert(is_array($parameters));

		$this->parameters = $parameters;
	}


	/**
	 * @param array|string $config file or configuration itself
	 */
	public function addConfig($config): void
	{
		assert(is_string($config) || is_array($config));
		$this->configs[] = $config;
	}


	public function getContainerFactory(): \Closure
	{
		return function (): DI\Container {
			$class = $this->loadContainer();
			$container = new $class([]);
			$container->initialize();

			return $container;
		};
	}


	/**
	 * Loads system DI container class and returns its name.
	 */
	protected function loadContainer(): string
	{
		$loader = new DI\ContainerLoader(
			$this->getCacheDirectory() . '/Mango.Tester.Infrastructure',
			$this->parameters['debugMode']
		);
		$class = $loader->load(
			function (DI\Compiler $compiler) {
				return $this->generateContainer($compiler);
			},
			[$this->parameters, $this->configs, PHP_VERSION_ID - PHP_RELEASE_VERSION]
		);
		return $class;
	}


	protected function generateContainer(DI\Compiler $compiler): string
	{
		$compiler->addConfig(['parameters' => $this->parameters]);

		$loader = new DI\Config\Loader;
		$fileInfo = [];
		foreach ($this->configs as $config) {
			if (is_string($config)) {
				$fileInfo[] = "// source: $config";
				$config = $loader->load($config);
			}
			$compiler->addConfig($config);
		}
		$compiler->addDependencies($loader->getDependencies());

		$compiler->addExtension('extensions', new DI\Extensions\ExtensionsExtension());
		$compiler->addExtension('mango.tester', new MangoTesterExtension());

		$classes = $compiler->compile();
		return implode("\n", $fileInfo) . "\n\n" . $classes;
	}


	protected function getCacheDirectory(): string
	{
		$dir = $this->parameters['tempDir'] . '/cache';
		Nette\Utils\FileSystem::createDir($dir);
		return $dir;
	}
}
