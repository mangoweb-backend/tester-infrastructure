<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Nette\DI\Container;
use Nette\DI\Helpers;
use Nette\DI\Resolver;
use Nette\Utils\Strings;
use ReflectionClass;


class MethodArgumentsResolver
{

	public function resolve(\ReflectionMethod $method, Container $appContainer, array $args)
	{
		$fixedArgs = $this->prepareArguments($method, $appContainer);

		$ref = new ReflectionClass(Resolver::class);
		$params = $ref->getMethod('autowireArguments')->getParameters();

		if ($params[2]->name == 'resolver') {
			return Resolver::autowireArguments($method, $args + $fixedArgs, $appContainer);
		} elseif ($params[2]->name == 'getter') {
			$getter = function (string $type, bool $single) use ($appContainer) {
				return $single
					? $appContainer->getByType($type)
					: array_map([$appContainer, 'getService'], $appContainer->findAutowired($type));
			};

			return Resolver::autowireArguments($method, $args + $fixedArgs, $getter);
		}
	}


	/**
	 * Autowires parametrics arguments by annotation with the following syntax:
	 *   (@)param string %any.param.name%
	 *              The string keyword is required mostly for PhpStorm compatibility.
	 *              Note that only positional arguments are not supported.
	 *
	 * Even though variable name is also allowed in the annotation, such as
	 *   (@)param $name %any.param.name%
	 *              the $name is not used.
	 *
	 * @return mixed[]
	 */
	protected function prepareArguments(\ReflectionMethod $method, Container $appContainer): array
	{
		$doc = $method->getDocComment() ?: '';

		$parameters = $appContainer->getParameters();
		$paramAnnotations = Strings::matchAll($doc, '~@param\s+(?P<name>\$\S+)\s+(?P<value>.*?)\s*$~m');

		$args = [];
		foreach ($paramAnnotations as $annotation) {
			$args[ltrim($annotation['name'], '$')] = Helpers::expand($annotation['value'], $parameters);
		}

		return $args;
	}
}
