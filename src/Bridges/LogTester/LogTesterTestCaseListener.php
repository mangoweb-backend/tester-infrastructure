<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\LogTester;

use Mangoweb\Tester\Infrastructure\ITestCaseListener;
use Mangoweb\Tester\Infrastructure\TestCase;
use Mangoweb\Tester\LogTester\LogTester;
use Psr\Log\LogLevel;


class LogTesterTestCaseListener implements ITestCaseListener
{
	/** @var LogTester */
	private $mailTester;


	public function __construct(LogTester $mailTester)
	{
		$this->mailTester = $mailTester;
	}


	public function setUp(TestCase $testCase): void
	{
	}


	public function tearDown(TestCase $testCase): void
	{
		$this->mailTester->assertNone(LogLevel::INFO);
	}
}
