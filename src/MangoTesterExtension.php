<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Mangoweb\MailTester\MailTester;
use Mangoweb\MailTester\TestMailer;
use Mangoweb\Tester\DatabaseCreator\Bridges\NetteDI\DatabaseCreatorExtension;
use Mangoweb\Tester\DatabaseCreator\DatabaseCreator;
use Mangoweb\Tester\DatabaseCreator\IDatabaseNameResolver;
use Mangoweb\Tester\Infrastructure\Bridges\Database\DatabaseCreatorHook;
use Mangoweb\Tester\Infrastructure\Bridges\LogMailer\LogTesterContainerHook;
use Mangoweb\Tester\Infrastructure\Bridges\LogTester\LogTesterTestCaseListener;
use Mangoweb\Tester\Infrastructure\Bridges\MailTester\MailTesterContainerHook;
use Mangoweb\Tester\Infrastructure\Bridges\MailTester\MailTesterTestCaseListener;
use Mangoweb\Tester\Infrastructure\Bridges\Mockery\MockeryContainerHook;
use Mangoweb\Tester\Infrastructure\Bridges\NextrasDbal\NextrasDbalHook;
use Mangoweb\Tester\Infrastructure\Bridges\PresenterTester\PresenterTesterTestCaseListener;
use Mangoweb\Tester\Infrastructure\Container\AppContainerFactory;
use Mangoweb\Tester\Infrastructure\Mocks\MocksContainerHook;
use Mangoweb\Tester\LogTester\LogTester;
use Mangoweb\Tester\LogTester\TestLogger;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Nette;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Statement;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Loaders\RobotLoader;
use Nette\Security\User;
use Nette\Utils\Validators;
use Nextras\Dbal\Connection;


class MangoTesterExtension extends CompilerExtension
{
	public const TAG_REQUIRE = 'mango.tester.require';
	public const TAG_HOOK = 'mango.tester.hook';

	public $defaults = [
		'baseUrl' => 'https://test.dev',
		'hooks' => [],
		'require' => [],
		'presenterTester' => FALSE,
		'logTester' => FALSE,
		'mailTester' => FALSE,
		'databaseCreator' => FALSE,
		'nextrasDbal' => FALSE,
		'mockery' => FALSE,
	];


	public function __construct()
	{
		$this->defaults['presenterTester'] = class_exists(PresenterTester::class);
		$this->defaults['logTester'] = class_exists(LogTester::class);
		$this->defaults['mailTester'] = class_exists(MailTester::class);
		$this->defaults['databaseCreator'] = class_exists(DatabaseCreator::class);
		$this->defaults['nextrasDbal'] = class_exists(Connection::class);
		$this->defaults['mockery'] = class_exists(\Mockery::class);
	}


	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);

		$this->registerRequiredServices($config['require']);
		$this->registerHooks($config['hooks']);

		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('appContainer'))
			->setClass(Container::class)
			->setAutowired(FALSE)
			->setDynamic(TRUE);

		$builder->addDefinition($this->prefix('mocksContainerHook'))
			->setClass(MocksContainerHook::class)
			->setArguments([$config['baseUrl']])
			->addTag(self::TAG_HOOK);

		$builder->addDefinition($this->prefix('containerFactory'))
			->setClass(AppContainerFactory::class);

		$builder->addDefinition($this->prefix('robotLoader'))
			->setClass(RobotLoader::class)
			->setDynamic(TRUE);

		$builder->addDefinition($this->prefix('methodArgumentResolver'))
			->setClass(MethodArgumentsResolver::class);
		$builder->addDefinition($this->prefix("testContext"))
			->setClass(TestContext::class)
			->setDynamic(TRUE);


		if ($config['presenterTester'] !== FALSE) {
			$this->setupPresenterTester($config);
		}

		if ($config['logTester'] !== FALSE) {
			$this->setupLogTester();
		}

		if ($config['mailTester'] !== FALSE) {
			$this->setupMailTester();
		}

		if ($config['databaseCreator'] !== FALSE) {
			$this->setupDatabaseCreator();
		}

		if ($config['nextrasDbal'] !== FALSE) {
			$this->setupNextrasDbal();
		}

		if ($config['mockery'] !== FALSE) {
			$builder->addDefinition($this->prefix('mockeryContainerHook'))
				->setClass(MockeryContainerHook::class)
				->addTag(self::TAG_HOOK);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		foreach ($builder->findByTag(self::TAG_REQUIRE) as $service => $attrs) {
			$def = $builder->getDefinition($service);
			$def->setDynamic(FALSE);
			if (is_string($attrs) && strpos($attrs, '\\') === FALSE) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getService'], [$attrs]));
			} elseif (is_string($attrs)) {
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$attrs]));
			} else {
				$type = $def->getClass();
				$def->setFactory(new Statement([$this->prefix('@appContainer'), 'getByType'], [$type]));
			}
		}
	}


	protected function registerHooks(array $hooks): void
	{
		$builder = $this->getContainerBuilder();
		$i = 0;
		foreach ($hooks as $hookClass) {
			$name = $i++ . preg_replace('#\W+#', '_', $hookClass);

			$builder->addDefinition($this->prefix($name))
				->setClass($hookClass)
				->addTag(self::TAG_HOOK);
		}
	}


	protected function registerRequiredServices(array $requiredServices): void
	{
		foreach ($requiredServices as $class) {
			$this->requireService($class);
		}
	}


	protected function setupNextrasDbal(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('nextrasDbalHook'))
			->setClass(NextrasDbalHook::class)
			->addTag(self::TAG_HOOK);

		$def = $builder->getDefinitionByType(Connection::class);
		$factory = $def->getFactory();
		assert($factory !== NULL);
		$args = $factory->arguments;
		$args['config'] = new Statement('array_merge(?, ?)', [
			$args['config'],
			[
				'database' => new Statement('@' . IDatabaseNameResolver::class . '::getDatabaseName'),
			],
		]);
		$def->setArguments($args);
		$def->addSetup(['@' . DatabaseCreator::class, 'createTestDatabase']);
	}


	protected function setupDatabaseCreator(): void
	{
		$builder = $this->getContainerBuilder();
		$dbCreatorExtension = $this->compiler->getExtensions(DatabaseCreatorExtension::class);
		assert(count($dbCreatorExtension) === 1, 'Register DatabaseCreator extension first');
		$builder->addDefinition($this->prefix('createDatabaseHook'))
			->setClass(DatabaseCreatorHook::class)
			->addTag(self::TAG_HOOK);
	}


	protected function setupLogTester(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('logTester'))
			->setClass(LogTester::class);
		$builder->addDefinition($this->prefix('logTesterContainerHook'))
			->setClass(LogTesterContainerHook::class)
			->addTag(self::TAG_HOOK);
		$builder->addDefinition($this->prefix('logTesterTestCaseListener'))
			->setClass(LogTesterTestCaseListener::class);
		$this->requireService(TestLogger::class);
	}


	protected function setupMailTester(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('mailTester'))
			->setClass(MailTester::class);
		$builder->addDefinition($this->prefix('mailTesterContainerHook'))
			->setClass(MailTesterContainerHook::class)
			->addTag(self::TAG_HOOK);
		$builder->addDefinition($this->prefix('mailTesterTestCaseListener'))
			->setClass(MailTesterTestCaseListener::class);
		$this->requireService(TestMailer::class);
	}


	protected function setupPresenterTester(array $config): void
	{
		$builder = $this->getContainerBuilder();
		if ($config['presenterTester'] === TRUE) {
			$config['presenterTester'] = [];
		}
		Validators::assert($config['presenterTester'], 'array');
		$config['presenterTester'] += [
			'identityFactory' => NULL,
		];
		$builder->addDefinition($this->prefix('presenterTester'))
			->setClass(PresenterTester::class)
			->setArguments(['baseUrl' => $config['baseUrl'], 'identityFactory' => $config['presenterTester']['identityFactory']])
			->addSetup(new Statement('?->? = ?',
				[
					$this->prefix('@presenterTesterTearDown'),
					'presenterTester',
					'@self',
				]));
		$builder->addDefinition($this->prefix('presenterTesterTearDown'))
			->setClass(PresenterTesterTestCaseListener::class);
		$this->requireService(IPresenterFactory::class);
		$this->requireService(User::class);
		$this->requireService(IRouter::class);
		$this->requireService(IRequest::class);
		$this->requireService(Session::class);
	}


	private function requireService(string $class)
	{
		$builder = $this->getContainerBuilder();
		$name = preg_replace('#\W+#', '_', $class);
		$builder->addDefinition($this->prefix($name))
			->setClass($class)
			->setDynamic(TRUE)
			->addTag(self::TAG_REQUIRE);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		parent::afterCompile($class);
		$class->addMethod('setAppContainer')
			->setBody('$this->addService(?, $container);', [$this->prefix('appContainer')])
			->addParameter('container');
	}

}
