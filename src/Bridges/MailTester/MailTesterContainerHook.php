<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\MailTester;

use Mangoweb\MailTester\TestMailer;
use Mangoweb\Tester\Infrastructure\Container\AppContainerHook;
use Nette\DI\ContainerBuilder;
use Nette\Mail\IMailer;


class MailTesterContainerHook extends AppContainerHook
{
	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->getDefinitionByType(IMailer::class)
			->setClass(TestMailer::class)
			->setFactory(TestMailer::class);
	}
}
