<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Nette\DI\Container;

interface ITestCaseListener
{
	public function setUp(TestCase $testCase, Container $applicationContainer, \ReflectionMethod $testMethod): void;

	public function tearDown(TestCase $testCase, Container $applicationContainer, \ReflectionMethod $testMethod): void;
}
