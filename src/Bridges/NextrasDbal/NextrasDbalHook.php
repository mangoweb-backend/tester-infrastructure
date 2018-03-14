<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\NextrasDbal;

use MangoShopTests\NextrasDbalServiceHelpers;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Nextras\Dbal\Connection;


class NextrasDbalHook implements IAppContainerHook
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
		$def = $builder->getDefinitionByType(Connection::class);
		NextrasDbalServiceHelpers::modifyConnectionDefinition($def);
	}


	public function onCreate(Container $applicationContainer): void
	{
	}
}
