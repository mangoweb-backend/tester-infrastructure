<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


interface IAppContainerHook
{
	public function getHash(): string;

	public function onConfigure(Configurator $appConfigurator): void;

	public function onCompile(ContainerBuilder $appContainerBuilder): void;

	public function onCreate(Container $appContainer): void;
}
