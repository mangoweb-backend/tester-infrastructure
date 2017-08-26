<?php declare(strict_types = 1);

namespace Mangoweb\Tester\Infrastructure\Mocks;

use Nette;


class Session extends Nette\Http\Session
{

	/** @var SessionSection[] */
	private $sections = [];

	/** @var bool */
	private $started = FALSE;

	/** @var bool */
	private $exists = FALSE;

	/** @var string */
	private $id = NULL;


	public function __construct()
	{

	}


	public function start()
	{
		$this->started = TRUE;
	}


	public function isStarted()
	{
		return $this->started;
	}


	public function close()
	{
		$this->started = FALSE;
	}


	public function destroy()
	{
		$this->started = FALSE;
	}


	public function exists()
	{
		return $this->exists;
	}


	public function setFakeExists(bool $exists): void
	{
		$this->exists = $exists;
	}


	public function regenerateId()
	{
	}


	public function getId()
	{
		return $this->id;
	}


	public function setFakeId($id)
	{
		$this->id = $id;
	}


	public function getSection($section, $class = SessionSection::class)
	{
		if (isset($this->sections[$section])) {
			return $this->sections[$section];
		}

		return $this->sections[$section] = parent::getSection($section, $class);
	}


	public function hasSection($section)
	{
		return isset($this->sections[$section]);
	}


	public function getIterator()
	{
		return new \ArrayIterator(array_keys($this->sections));
	}


	public function clean()
	{
	}


	public function setName($name)
	{
		return $this;
	}


	public function getName()
	{
		return '';
	}


	public function setOptions(array $options)
	{
		return $this;
	}


	public function getOptions()
	{
		return [];
	}


	public function setExpiration($time)
	{
		return $this;
	}


	public function setCookieParameters($path, $domain = NULL, $secure = NULL)
	{
		return $this;
	}


	public function getCookieParameters()
	{
		return NULL;
	}


	public function setSavePath($path)
	{
		return $this;
	}


	public function setStorage(Nette\Http\ISessionStorage $storage)
	{
	}


	public function setHandler(\SessionHandlerInterface $handler)
	{
	}

}
