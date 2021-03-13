<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;


class TestContext
{
	/** @var class-string */
	private $testCaseClass;

	/** @var string */
	private $testMethod;


	/**
	 * @param class-string $testCaseClass
	 */
	public function __construct(string $testCaseClass, string $testMethod)
	{
		$this->testCaseClass = $testCaseClass;
		$this->testMethod = $testMethod;
	}


	/**
	 * @return class-string
	 */
	public function getTestCaseClass(): string
	{
		return $this->testCaseClass;
	}


	public function getTestMethod(): string
	{
		return $this->testMethod;
	}
}
