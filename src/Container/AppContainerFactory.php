<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Mangoweb\Tester\Infrastructure\MangoTesterExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class AppContainerFactory
{
	/** @var IAppConfiguratorFactory */
	private $appConfiguratorFactory;


	public function __construct(IAppConfiguratorFactory $appConfiguratorFactory)
	{
		$this->appConfiguratorFactory = $appConfiguratorFactory;
	}


	public function create(Container $testContainer, ?IAppContainerHook $testCaseHook): Container
	{
		$configurator = $this->appConfiguratorFactory->create($testContainer);

		$hook = $this->getHook($testContainer);
		if ($testCaseHook) {
			$hook = new AppContainerHookList([$hook, $testCaseHook]);
		}
		$this->setupConfigurator($testContainer, $configurator, $hook);

		$appContainer = $configurator->createContainer();
		$hook->onCreate($appContainer);
		assert($appContainer instanceof Container);

		return $appContainer;
	}


	protected function setupConfigurator(Container $testContainer, Configurator $configurator, IAppContainerHook $hook): void
	{
		$configurator->addParameters([
			'hooksHash' => md5(serialize(get_class($hook))),
		]);
		$hook->onConfigure($configurator);
		$configurator->onCompile[] = function ($configurator, Compiler $compiler) use ($hook) {
			$compilerExtension = new CompilerHookExtension();
			$compilerExtension->onBeforeCompile[] = function (ContainerBuilder $builder) use ($hook) {
				$hook->onCompile($builder);
			};
			$compiler->addExtension('tests.beforeCompile', $compilerExtension);
		};

		$configurator->addParameters([
			'testContainerParameters' => $testContainer->getParameters(),
		]);
	}


	protected function getHook(Container $testContainer): IAppContainerHook
	{
		$hooks = [];

		foreach ($testContainer->findByTag(MangoTesterExtension::TAG_HOOK) as $hookName => $_) {
			$hook = $testContainer->getService($hookName);
			assert($hook instanceof IAppContainerHook);
			$hooks[] = $hook;
		}
		return new AppContainerHookList($hooks);
	}
}
