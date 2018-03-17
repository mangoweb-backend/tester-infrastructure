<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Mangoweb\Tester\Infrastructure\Container\AppContainerFactory;
use Mangoweb\Tester\Infrastructure\Container\AppContainerHookList;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Nette\DI\Container;
use Nette\Utils\Strings;
use Tester\AssertException;
use Tester\Dumper;


class TestCase
{

	/** @var bool */
	private $handleErrors = FALSE;

	/** @var callable|NULL|FALSE */
	private $prevErrorHandler = FALSE;

	/** @var Container */
	private $testContainer;

	/** @var Container */
	private $applicationContainer;


	public static function run(callable $testContainerFactory): void
	{
		$runner = new TestCaseRunner(get_called_class(), $testContainerFactory);
		$runner->run();
	}


	public static function runMethod(callable $testContainerFactory, string $method, array $args)
	{
		$testContainer = $testContainerFactory();
		assert($testContainer instanceof Container);
		$testContainer->addService($testContainer->findByType(TestContext::class)[0], new TestContext(get_called_class(), $method));

		$rm = new \ReflectionMethod(get_called_class(), $method);
		$appContainer = static::createApplicationContainer($testContainer, $rm);
		$testContainer->setAppContainer($appContainer);

		$testCase = $testContainer->createInstance(get_called_class());
		assert($testCase instanceof self);
		$testCase->testContainer = $testContainer;
		$testCase->applicationContainer = $appContainer;
		$result = $testCase->execute($rm, $args);
		unset($testContainer, $appContainer, $testCase);
		gc_collect_cycles();

		return $result;
	}


	protected static function createApplicationContainer(Container $testContainer, \ReflectionMethod $rm)
	{
		$hooks = [];
		$hooks[] = static::getContainerHook($testContainer);
		$doc = $rm->getDocComment();
		$hookNames = Strings::matchAll($doc, '~\*\s+@hook\s+([\w_\\\\]+)(?:\s+.*)?$~m', PREG_PATTERN_ORDER);
		foreach ($hookNames[1] as $hookName) {
			if (class_exists($hookName)) {
				$hooks[] = $testContainer->createInstance($hookName);
			} elseif (method_exists(get_called_class(), $hookName)) {
				$hookRm = new \ReflectionMethod(get_called_class(), $hookName);
				assert($hookRm->isStatic());
				$methodCallback = [get_called_class(), $hookName];
				assert(is_callable($methodCallback));
				$hooks[] = $testContainer->callMethod($methodCallback);
			} else {
				throw new \LogicException("Hook $hookName not found");
			}
		}

		$factory = $testContainer->getByType(AppContainerFactory::class);
		assert($factory instanceof AppContainerFactory);

		return $factory->create($testContainer, new AppContainerHookList(array_filter($hooks)));
	}


	/**
	 * Override to add test case specific app hook
	 */
	protected static function getContainerHook(Container $testContainer): ?IAppContainerHook
	{
		return NULL;
	}


	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp(): void
	{
	}


	protected function executeSetupListeners(\ReflectionMethod $method): void
	{
		foreach ($this->testContainer->findByType(ITestCaseListener::class) as $serviceName) {
			$service = $this->testContainer->getService($serviceName);
			assert($service instanceof ITestCaseListener);
			$service->setUp($this, $this->applicationContainer,$method);
		}
	}


	/**
	 * This method is called after a test is executed.
	 */
	protected function tearDown(): void
	{
	}


	protected function executeTearDownListeners(\ReflectionMethod $method): void
	{
		foreach ($this->testContainer->findByType(ITestCaseListener::class) as $serviceName) {
			$service = $this->testContainer->getService($serviceName);
			assert($service instanceof ITestCaseListener);
			$service->tearDown($this, $this->applicationContainer, $method);
		}
	}


	protected function execute(\ReflectionMethod $method, array $args)
	{
		if ($this->prevErrorHandler === FALSE) {
			$this->prevErrorHandler = set_error_handler(function ($severity) {
				if ($this->handleErrors && ($severity & error_reporting()) === $severity) {
					$this->handleErrors = FALSE;
					$this->silentTearDown();
				}

				return $this->prevErrorHandler ? call_user_func_array($this->prevErrorHandler, func_get_args()) : FALSE;
			});
		}


		try {
			$this->applicationContainer->callInjects($this);

			$this->executeSetupListeners($method);
			$this->setUp();

			$this->handleErrors = TRUE;
			try {
				$result = $this->invoke($method, $args);
			} catch (\Exception $e) {
				$this->handleErrors = FALSE;
				$this->silentTearDown();
				throw $e;
			}
			$this->handleErrors = FALSE;

			$this->tearDown();
			$this->executeTearDownListeners($method);

			return $result;
		} catch (AssertException $e) {
			throw $e->setMessage("$e->origMessage in {$method->getName()}(" . (substr(Dumper::toLine($args), 1, -1)) . ')');
		} finally {
			restore_error_handler();
			$this->prevErrorHandler = FALSE;
		}
	}


	private function silentTearDown(): void
	{
		set_error_handler(function () {
		});
		try {
			$this->tearDown();
		} catch (\Exception $e) {
		}
		restore_error_handler();
	}


	protected function invoke(\ReflectionMethod $method, array $args)
	{
		if (count($method->getParameters()) > 0) {
			$resolver = $this->testContainer->getByType(MethodArgumentsResolver::class);
			assert($resolver instanceof MethodArgumentsResolver);
			$args = $resolver->resolve($method, $this->applicationContainer, $args);
		}

		return call_user_func_array([$this, $method->getName()], $args);
	}

}
