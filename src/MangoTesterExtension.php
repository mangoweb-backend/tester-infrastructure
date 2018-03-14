<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\Bridges\Mockery\MockeryContainerHook;
use Mangoweb\Tester\Infrastructure\Container\AppContainerFactory;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Statement;


class MangoTesterExtension extends CompilerExtension
{
	public const TAG_REQUIRE = 'mango.tester.require';
	public const TAG_HOOK = 'mango.tester.hook';

	public $defaults = [
		'hooks' => [],
		'require' => [],
		'mockery' => FALSE,
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

		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('appContainer'))
			->setClass(Container::class)
			->setAutowired(FALSE)
			->setDynamic(TRUE);

		$builder->addDefinition($this->prefix('containerFactory'))
			->setClass(AppContainerFactory::class);

		$builder->addDefinition($this->prefix('methodArgumentResolver'))
			->setClass(MethodArgumentsResolver::class);
		$builder->addDefinition($this->prefix('testContext'))
			->setClass(TestContext::class)
			->setDynamic(TRUE);

		if ($config['mockery'] !== FALSE) {
			$builder->addDefinition($this->prefix('mockeryContainerHook'))
				->setClass(MockeryContainerHook::class)
				->addTag(self::TAG_HOOK);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		foreach ($builder->findByTag(self::TAG_REQUIRE) as $service => $attrs) {
			$def = $builder->getDefinition($service);
			$def->setDynamic(FALSE);
			if (is_string($attrs) && strpos($attrs, '\\') === FALSE) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getService'], [$attrs]));
			} elseif (is_string($attrs)) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$attrs]));
			} else {
				$type = $def->getClass();
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$type]));
			}
		}
	}


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


	protected function registerRequiredServices(array $requiredServices): void
	{
		foreach ($requiredServices as $class) {
			$this->requireService($class);
		}
	}


	private function requireService(string $class)
	{
		$builder = $this->getContainerBuilder();
		$name = preg_replace('#\W+#', '_', $class);
		$builder->addDefinition($this->prefix($name))
			->setClass($class)
			->setDynamic(TRUE)
			->addTag(self::TAG_REQUIRE);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		parent::afterCompile($class);
		$class->addMethod('setAppContainer')
			->setBody('$this->addService(?, $container);', [$this->prefix('appContainer')])
			->addParameter('container');
	}

}
