<?php

namespace Demoniqus\EntityProcessor\Tests\Dummy\Utils;

use Demoniqus\EntityProcessor\Interfaces\ServiceExtractorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ServiceExtractor implements ServiceExtractorInterface
{
//region SECTION: Fields

	private ContainerInterface $container;
//endregion Fields

//region SECTION: Constructor

	public function __construct(
		ContainerInterface $container
	)
	{
		$this->container = $container;
	}
//endregion Constructor 

//region SECTION: Protected 

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters 
	public function get($id)
	{
		return $this->container->get($id);
	}

	public function has($id): bool
	{
		return $this->container->has($id);
	}
//endregion Getters/Setters
}