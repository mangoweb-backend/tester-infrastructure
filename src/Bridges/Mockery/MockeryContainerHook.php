<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\Mockery;

use Mangoweb\Tester\Infrastructure\Container\AppContainerHook;
use Mangoweb\Tester\Infrastructure\TestContext;
use Mockery\MockInterface;
use Nette\DI\Container;
use Nette\Utils\Strings;

class MockeryContainerHook extends AppContainerHook
{

	/** @var TestContext */
	private $testContext;


	public function __construct(TestContext $testContext)
	{
		$this->testContext = $testContext;
	}


	public function onCreate(Container $applicationContainer): void
	{
		$rc = new \ReflectionClass($this->testContext->getTestCaseClass());
		$rm = $rc->getMethod($this->testContext->getTestMethod());
		$doc = $rm->getDocComment() ?: '';
		$params = Strings::matchAll($doc, '~\*\s+@param\s+([\w_\\\\|]+)\s+(\$[\w_]+)(?:\s+.*)?$~Um');
		foreach ($params as [, $types, $paramName]) {
			$types = explode('|', $types);
			if (count($types) !== 2) {
				continue;
			}
			[$requiredType, $mockeryType] = $types;
			$requiredType = \Nette\Utils\Reflection::expandClassName($requiredType, $rc);
			$mockeryType = \Nette\Utils\Reflection::expandClassName($mockeryType, $rc);
			if ($mockeryType !== MockInterface::class) {
				continue;
			}
			$applicationContainer->addService($applicationContainer->findByType($requiredType)[0], \Mockery::mock($requiredType));
		}

	}
}
