<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


interface IAppContainerHook
{

	public function onConfigure(Configurator $configurator): void;


	public function onCompile(ContainerBuilder $builder): void;


	public function onCreate(Container $applicationContainer): void;

}
