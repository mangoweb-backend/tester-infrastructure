<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\Bridges\Mockery\MockeryContainerHook;
use Mangoweb\Tester\Infrastructure\Container\AppContainerFactory;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Statement;


class MangoTesterExtension extends CompilerExtension
{
	public const TAG_REQUIRE = 'mango.tester.require';
	public const TAG_HOOK = 'mango.tester.hook';

	/** @var mixed[]  */
	public $defaults = [
		'hooks' => [],
		'require' => [],
		'appContainer' => [],
		'mockery' => false,
	];


	public function __construct()
	{
		$this->defaults['mockery'] = class_exists(\Mockery::class);
	}


	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);

		$this->registerRequiredServices($config['require']);
		$this->registerHooks($config['hooks']);
		$this->registerAppConfiguratorFactory($config['appContainer']);

		$builder = $this->getContainerBuilder();

		$this->addDynamic($this->prefix('appContainer'), Container::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('containerFactory'))
			->setClass(AppContainerFactory::class);

		$builder->addDefinition($this->prefix('methodArgumentResolver'))
			->setClass(MethodArgumentsResolver::class);

		$this->addDynamic($this->prefix('testContext'), TestContext::class);

		if ($config['mockery'] !== false) {
			$builder->addDefinition($this->prefix('mockeryContainerHook'))
				->setClass(MockeryContainerHook::class)
				->addTag(self::TAG_HOOK);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		foreach ($builder->findByTag(self::TAG_REQUIRE) as $service => $attrs) {
			/** @var ServiceDefinition $def */
			$def = $builder->getDefinition($service);
			if (is_string($attrs) && strpos($attrs, '\\') === false) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getService'], [$attrs]));
			} elseif (is_string($attrs)) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$attrs]));
			} else {
				$type = $def->getClass();
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$type]));
			}
		}
	}


	/**
	 * @param class-string[] $hooks
	 */
	protected function registerHooks(array $hooks): void
	{
		$builder = $this->getContainerBuilder();
		$i = 0;
		foreach ($hooks as $hookClass) {
			$name = $i++ . preg_replace('#\W+#', '_', $hookClass);

			$builder->addDefinition($this->prefix($name))
				->setClass($hookClass)
				->addTag(self::TAG_HOOK);
		}
	}


	/**
	 * @param string[] $requiredServices
	 */
	protected function registerRequiredServices(array $requiredServices): void
	{
		foreach ($requiredServices as $class) {
			$this->requireService($class);
		}
	}


	private function requireService(string $class): void
	{
		$builder = $this->getContainerBuilder();
		$name = preg_replace('#\W+#', '_', $class);
		$builder->addDefinition($this->prefix($name))
			->setClass($class)
			->addTag(self::TAG_REQUIRE);
	}


	/**
	 * @param mixed[] $config
	 */
	private function registerAppConfiguratorFactory(array $config): void
	{
		if ($config === []) {
			return;
		}
		$builder = $this->getContainerBuilder();
		$def = $builder->addDefinition($this->prefix('appConfiguratorFactory'))
			->setFactory(DefaultAppConfiguratorFactory::class, [
				'configFiles' => $config['configs'] ?? []
			]);

		if (($config['overrideDefaultExtensions'] ?? false) !== true) {
			$def->addSetup('disableDefaultExtensionsOverride');
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		parent::afterCompile($class);
		$class->addMethod('setAppContainer')
			->setBody('$this->addService(?, $container);', [$this->prefix('appContainer')])
			->addParameter('container');
	}


	/**
	 * @return Nette\DI\Definitions\ImportedDefinition|Nette\DI\ServiceDefinition
	 */
	private function addDynamic(string $name, string $className)
	{
		$builder = $this->getContainerBuilder();
		if (class_exists(Nette\DI\Definitions\ImportedDefinition::class)) {
			return $builder->addImportedDefinition($name)->setType($className);
		}
		$def = $builder->addDefinition($name);
		$def->setClass($className);
		$def->setDynamic(true);

		return $def;
	}
}
