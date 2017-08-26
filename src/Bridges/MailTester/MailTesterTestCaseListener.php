<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Bridges\MailTester;

use Mangoweb\MailTester\MailTester;
use Mangoweb\Tester\Infrastructure\ITestCaseListener;
use Mangoweb\Tester\Infrastructure\TestCase;


class MailTesterTestCaseListener implements ITestCaseListener
{

	/** @var MailTester */
	private $mailTester;


	public function __construct(MailTester $mailTester)
	{
		$this->mailTester = $mailTester;
	}


	public function setUp(TestCase $testCase): void
	{
	}


	public function tearDown(TestCase $testCase): void
	{
		$this->mailTester->assertNone();
	}

}
