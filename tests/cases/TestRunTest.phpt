<?php declare(strict_types = 1);

namespace MangowebTests\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\TestCase;
use Nette\DI\Container;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * @testCase
 */
class TestRunTest extends TestCase
{
	public function testEcho(\DateTimeImmutable $containerDependency)
	{
		Assert::true(true);
	}
}

TestRunTest::run(Bootstrap::FACTORY);
