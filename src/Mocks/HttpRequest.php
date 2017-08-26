<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Mocks;

use Nette\Http\Request;


class HttpRequest extends Request
{

	/** @var array */
	private $headers = [];

	/** @var string|NULL */
	private $body;


	public function setRawBody(?string $body)
	{
		$this->body = $body;
	}


	public function getRawBody()
	{
		return $this->body ?? parent::getRawBody();
	}


	public function setHeader(string $name, string $value)
	{
		$this->headers[$name] = $value;
	}


	public function getHeader($header, $default = NULL)
	{
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		}
		return parent::getHeader($header, $default);
	}


	public function getHeaders()
	{
		return array_merge(parent::getHeaders(), $this->headers);
	}

}
