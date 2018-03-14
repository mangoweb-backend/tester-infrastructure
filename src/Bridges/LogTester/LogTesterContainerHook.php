<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\LogMailer;

use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Mangoweb\Tester\LogTester\TestLogger;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Psr\Log\LoggerInterface;


class LogTesterContainerHook implements IAppContainerHook
{
	public function getHash(): string
	{
		return __CLASS__;
	}


	public function onConfigure(Configurator $configurator): void
	{
	}


	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->getDefinitionByType(LoggerInterface::class)
			->setClass(TestLogger::class)
			->setFactory(TestLogger::class);
	}


	public function onCreate(Container $applicationContainer): void
	{
	}
}
