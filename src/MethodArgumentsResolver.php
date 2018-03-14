<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure;

use Nette\DI\Container;
use Nette\DI\Helpers;
use Nette\Utils\Strings;


class MethodArgumentsResolver
{

	public function resolve(\ReflectionMethod $method, Container $appContainer, array $args)
	{
		$fixedArgs = $this->prepareArguments($method, $appContainer);

		return Helpers::autowireArguments($method, $args + $fixedArgs, $appContainer);
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
		$doc = $method->getDocComment();

		$parameters = $appContainer->getParameters();
		$paramAnnotations = Strings::matchAll($doc, '~@param\s+(?P<name>\$\S+)\s+(?P<value>.*?)\s*$~m');

		$args = [];
		foreach ($paramAnnotations as $annotation) {
			$args[ltrim($annotation['name'], '$')] = Helpers::expand($annotation['value'], $parameters);
		}

		return $args;
	}
}
