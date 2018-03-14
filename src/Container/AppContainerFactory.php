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
		$hook = $this->getHook($testContainer, $testCaseHook);

		$appConfigurator = $this->appConfiguratorFactory->create($testContainer);
		$this->setupConfigurator($testContainer, $appConfigurator, $hook);

		$appContainer = $appConfigurator->createContainer();
		$hook->onCreate($appContainer);

		return $appContainer;
	}


	protected function setupConfigurator(Container $testContainer, Configurator $appConfigurator, IAppContainerHook $hook): void
	{
		$hook->onConfigure($appConfigurator);

		$appConfigurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($hook): void {
			$compilerExtension = new CompilerHookExtension();
			$compilerExtension->onBeforeCompile[] = function (ContainerBuilder $builder) use ($hook): void {
				$hook->onCompile($builder);
			};

			$compiler->addExtension('mango.tester.beforeCompile', $compilerExtension);
		};

		$appConfigurator->addParameters([
			'hookHash' => $hook->getHash(),
			'testContainerParameters' => $testContainer->getParameters(),
		]);
	}


	protected function getHook(Container $testContainer, ?IAppContainerHook $testCaseHook): IAppContainerHook
	{
		$hooks = [];

		foreach ($testContainer->findByTag(MangoTesterExtension::TAG_HOOK) as $hookName => $_) {
			$hook = $testContainer->getService($hookName);
			assert($hook instanceof IAppContainerHook);
			$hooks[] = $hook;
		}

		if ($testCaseHook !== null) {
			$hooks[] = $testCaseHook;
		}

		return new AppContainerHookList($hooks);
	}
}
