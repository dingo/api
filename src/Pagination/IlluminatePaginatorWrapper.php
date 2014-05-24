<?php namespace Dingo\Api\Pagination;

use Illuminate\Pagination\Paginator;
use League\Fractal\Pagination\PaginatorInterface;

class IlluminatePaginatorWrapper implements PaginatorInterface
{

	/**
	 * @var \Illuminate\Pagination\Paginator
	 */
	protected $paginator;

	/**
	 * @param \Illuminate\Pagination\Paginator $paginator
	 */
	public function __construct(Paginator $paginator)
	{
		$this->paginator = $paginator;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCurrentPage()
	{
		return $this->paginator->getCurrentPage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLastPage()
	{
		return $this->paginator->getLastPage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTotal()
	{
		return $this->paginator->getTotal();
	}

	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->paginator->count();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPerPage()
	{
		return $this->paginator->getPerPage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUrl($page)
	{
		return $this->paginator->getUrl($page);
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $args)
	{
		if (! method_exists($this->paginator, $method))
		{
			throw new \BadMethodCallException("Unable to find method {$method} on paginator instance.");
		}

		return call_user_func_array([$this->paginator, $method], $args);
	}

}
