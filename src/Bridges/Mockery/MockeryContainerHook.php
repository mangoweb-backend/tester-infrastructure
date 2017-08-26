<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\Mockery;

use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Mangoweb\Tester\Infrastructure\TestContext;
use Mockery\MockInterface;
use Nette\Configurator;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Nette\Reflection\AnnotationsParser;
use Nette\Utils\Strings;

class MockeryContainerHook implements IAppContainerHook
{

	/** @var TestContext */
	private $testContext;


	public function __construct(TestContext $testContext)
	{
		$this->testContext = $testContext;
	}


	public function onConfigure(Configurator $configurator): void
	{

	}


	public function onCompile(ContainerBuilder $builder): void
	{
	}


	public function onCreate(Container $applicationContainer): void
	{
		$rc = new \ReflectionClass($this->testContext->getTestCaseClass());
		$rm = $rc->getMethod($this->testContext->getTestMethod());
		$doc = $rm->getDocComment();
		$params = Strings::matchAll($doc, '~\*\s+@param\s+([\w_\\\\|]+)\s+(\$[\w_]+)(?:\s+.*)?$~m');
		foreach ($params as [, $types, $paramName]) {
			$types = explode('|', $types);
			if (count($types) !== 2) {
				continue;
			}
			[$requiredType, $mockeryType] = $types;
			$requiredType = AnnotationsParser::expandClassName($requiredType, $rc);
			$mockeryType = AnnotationsParser::expandClassName($mockeryType, $rc);
			if ($mockeryType !== MockInterface::class) {
				continue;
			}
			$applicationContainer->addService($applicationContainer->findByType($requiredType)[0], \Mockery::mock($requiredType));
		}

	}

}
