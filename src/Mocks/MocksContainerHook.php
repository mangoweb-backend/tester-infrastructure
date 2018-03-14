<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Mocks;

use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Nette\DI\Statement;
use Nette\Http\Request;
use Nette\Http\UrlScript;


class MocksContainerHook implements IAppContainerHook
{

	/** @var string */
	private $baseUrl;


	public function __construct(string $baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}


	public function getHash(): string
	{
		return __CLASS__;
	}


	public function onConfigure(Configurator $configurator): void
	{

	}


	public function onCompile(ContainerBuilder $builder): void
	{
		$builder->getDefinition('http.request')
			->setClass(Request::class)
			->setFactory(HttpRequest::class, [new Statement(UrlScript::class, [$this->baseUrl])]);
		$builder->getDefinition('session.session')
			->setClass(\Nette\Http\Session::class)
			->setFactory(Session::class);
	}


	public function onCreate(Container $applicationContainer): void
	{

	}

}
