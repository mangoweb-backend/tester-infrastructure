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

	/** @var string */
	private $testCaseClass;


	public function __construct(string $testCaseClass, callable $testContainerFactory)
	{
		$this->testContainerFactory = $testContainerFactory;
		$this->testCaseClass = $testCaseClass;
	}


	public function run(): void
	{
		$methods = array_values(preg_grep(self::METHOD_PATTERN, array_map(function (\ReflectionMethod $rm) {
			return $rm->getName();
		}, (new \ReflectionClass($this->testCaseClass))->getMethods())));

		if (isset($_SERVER['argv']) && ($tmp = preg_filter('#--method=([\w-]+)$#Ai', '$1', $_SERVER['argv']))) {
			$method = reset($tmp);
			if ($method === self::LIST_METHODS) {
				Environment::$checkAssertions = FALSE;
				header('Content-Type: text/plain');
				echo '[' . implode(',', $methods) . ']';
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

		$info = \Tester\Helpers::parseDocComment($method->getDocComment()) + ['dataprovider' => NULL];

		$data = [];
		$defaultParams = [];
		foreach ($method->getParameters() as $param) {
			$defaultParams[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
		}

		foreach ((array) $info['dataprovider'] as $provider) {
			$res = self::getData($provider);
			if (!is_array($res) && !$res instanceof \Traversable) {
				throw new TestCaseException("Data provider $provider() doesn't return array or Traversable.");
			}
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


	private function callTestMethod(string $method, array $args)
	{
		return ($this->testCaseClass)::runMethod($this->testContainerFactory, $method, $args);
	}


	protected function getData($provider): iterable
	{
		if (strpos($provider, '.') === FALSE) {
			return $this->callTestMethod($provider, []);
		}
		$rc = new \ReflectionClass($this->testCaseClass);
		[$file, $query] = DataProvider::parseAnnotation($provider, $rc->getFileName());
		return DataProvider::load($file, $query);
	}

}
