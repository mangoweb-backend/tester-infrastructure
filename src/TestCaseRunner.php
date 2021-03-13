<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Tester\DataProvider;
use Tester\Environment;
use Tester\TestCaseException;


class TestCaseRunner
{
	private const LIST_METHODS = 'nette-tester-list-methods';
	private const METHOD_PATTERN = '#^test[A-Z0-9_]#';

	/** @var callable */
	private $testContainerFactory;

	/** @var class-string */
	private $testCaseClass;


	/**
	 * @param class-string $testCaseClass
	 */
	public function __construct(string $testCaseClass, callable $testContainerFactory)
	{
		$this->testContainerFactory = $testContainerFactory;
		$this->testCaseClass = $testCaseClass;
	}


	public function run(): void
	{
		$methods = preg_grep(self::METHOD_PATTERN, array_map(function (\ReflectionMethod $rm) {
			return $rm->getName();
		}, (new \ReflectionClass($this->testCaseClass))->getMethods()));
		assert($methods !== false);
		$methods = array_values($methods);

		if (isset($_SERVER['argv']) && ($tmp = preg_filter('#--method=([\w-]+)$#Ai', '$1', $_SERVER['argv']))) {
			$method = reset($tmp);
			if ($method === self::LIST_METHODS) {
				Environment::$checkAssertions = FALSE;
				header('Content-Type: text/plain');
				if (method_exists(\Tester\TestCase::class, 'sendMethodList')) {
					echo "\n";
					echo 'TestCase:' . static::class . "\n";
					echo 'Method:' . implode("\nMethod:", $methods) . "\n";
				} else {
					// legacy format
					echo '[' . implode(',', $methods) . ']';
				}
				return;
			}
			$this->runMethod($method);

		} else {
			foreach ($methods as $method) {
				$this->runMethod($method);
			}
		}
	}


	public function runMethod(string $method): void
	{
		if (!method_exists($this->testCaseClass, $method)) {
			throw new TestCaseException("Method '$method' does not exist.");
		} elseif (!preg_match(self::METHOD_PATTERN, $method)) {
			throw new TestCaseException("Method '$method' is not a testing method.");
		}

		$method = new \ReflectionMethod($this->testCaseClass, $method);
		if (!$method->isPublic()) {
			throw new TestCaseException("Method {$method->getName()} is not public. Make it public or rename it.");
		}

		$info = \Tester\Helpers::parseDocComment($method->getDocComment() ?: '') + ['dataprovider' => NULL];

		$data = [];
		$defaultParams = [];
		foreach ($method->getParameters() as $param) {
			$defaultParams[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
		}

		foreach ((array) $info['dataprovider'] as $provider) {
			$res = self::getData($provider);
			foreach ($res as $set) {
				$data[] = is_string(key($set)) ? array_merge($defaultParams, $set) : $set;
			}
		}

		if (!$info['dataprovider']) {
			$data[] = [];
		}
		foreach ($data as $args) {
			$this->callTestMethod($method->getName(), $args);
		}
	}


	/**
	 * @param mixed[] $args
	 * @return mixed
	 */
	private function callTestMethod(string $method, array $args)
	{
		return ($this->testCaseClass)::runMethod($this->testContainerFactory, $method, $args);
	}


	/**
	 * @return iterable<mixed>
	 */
	protected function getData(string $provider): iterable
	{
		if (strpos($provider, '.') === FALSE) {
			return $this->callTestMethod($provider, []);
		}
		$rc = new \ReflectionClass($this->testCaseClass);
		$fileName = $rc->getFileName();
		assert($fileName !== false);
		[$file, $query] = DataProvider::parseAnnotation($provider, $fileName);
		return DataProvider::load($file, $query);
	}

}
