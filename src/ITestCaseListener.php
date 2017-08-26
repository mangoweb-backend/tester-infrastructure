<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;


interface ITestCaseListener
{

	public function setUp(TestCase $testCase): void;


	public function tearDown(TestCase $testCase): void;

}
