<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class AppContainerHook implements IAppContainerHook
{
	public function getHash(): string
	{
		return __CLASS__;
	}


	public function onConfigure(Configurator $appConfigurator): void
	{
	}


	public function onCompile(ContainerBuilder $appContainerBuilder): void
	{
	}


	public function onCreate(Container $appContainer): void
	{
	}
}
