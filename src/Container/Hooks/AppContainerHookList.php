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


	public function getHash(): string
	{
		return md5(serialize(array_map(
			static function (IAppContainerHook $hook): string {
				return $hook->getHash();
			},
			$this->hooks
		)));
	}


	public function onConfigure(Configurator $appConfigurator): void
	{
		foreach ($this->hooks as $hook) {
			$hook->onConfigure($appConfigurator);
		}
	}


	public function onCompile(ContainerBuilder $appContainerBuilder): void
	{
		foreach ($this->hooks as $hook) {
			$hook->onCompile($appContainerBuilder);
		}
	}


	public function onCreate(Container $appContainer): void
	{
		foreach ($this->hooks as $hook) {
			$hook->onCreate($appContainer);
		}
	}
}
