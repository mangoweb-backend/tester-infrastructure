<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\LogMailer;

use Mangoweb\Tester\Infrastructure\Container\AppContainerHook;
use Mangoweb\Tester\LogTester\TestLogger;
use Nette\DI\ContainerBuilder;
use Psr\Log\LoggerInterface;


class LogTesterContainerHook extends AppContainerHook
{
	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->getDefinitionByType(LoggerInterface::class)
			->setClass(TestLogger::class)
			->setFactory(TestLogger::class);
	}
}
