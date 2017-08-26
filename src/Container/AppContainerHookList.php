<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class AppContainerHookList implements IAppContainerHook
{

	/** @var IAppContainerHook[] */
	private $hooks;


	/**
	 * @param IAppContainerHook[] $hooks
	 */
	public function __construct(array $hooks)
	{
		$this->hooks = $hooks;
	}


	public function onConfigure(Configurator $configurator): void
	{
		$hookHash = (function () {
			return $this->parameters['hookHash'] ?? '';
		})->bindTo($configurator, Configurator::class)();
		$configurator->addParameters([
			'hookHash' => md5(serialize([$hookHash, array_map('get_class', $this->hooks)])),
		]);
		foreach ($this->hooks as $hook) {
			$hook->onConfigure($configurator);
		}
	}


	public function onCompile(ContainerBuilder $builder): void
	{
		foreach ($this->hooks as $hook) {
			$hook->onCompile($builder);
		}
	}


	public function onCreate(Container $applicationContainer): void
	{
		foreach ($this->hooks as $hook) {
			$hook->onCreate($applicationContainer);
		}
	}

}
