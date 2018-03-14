<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\MailTester;

use Mangoweb\MailTester\TestMailer;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class MailTesterContainerHook implements IAppContainerHook
{

	public function onConfigure(Configurator $configurator): void
	{

	}


	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->getDefinition('mail.mailer')
			->setClass(TestMailer::class)
			->setFactory(TestMailer::class);
	}


	public function onCreate(Container $applicationContainer): void
	{

	}
}
