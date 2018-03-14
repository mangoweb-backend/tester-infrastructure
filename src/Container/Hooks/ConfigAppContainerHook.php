<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Container;

use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;


class ConfigAppContainerHook implements IAppContainerHook
{
	/** @var array|string */
	private $config;


	/**
	 * @param array|string $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}


	public function getHash(): string
	{
		return __CLASS__;
	}


	public function onConfigure(Configurator $appConfigurator): void
	{
		$appConfigurator->addConfig($this->config);
	}


	public function onCompile(ContainerBuilder $appContainerBuilder): void
	{

	}


	public function onCreate(Container $appContainer): void
	{

	}
}
