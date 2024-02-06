<?php

namespace Demoniqus\EntityProcessor\Tests\unit\Processor;

use Demoniqus\EntityProcessor\Factory\DtoCreatorFactory;
use Demoniqus\EntityProcessor\Factory\EntityRemoverFactory;
use Demoniqus\EntityProcessor\Factory\EntitySaverFactory;
use Demoniqus\EntityProcessor\Interfaces\ServiceExtractorInterface;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\Tests\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityProcessorTest extends WebTestCase
{
//region SECTION: Fields
	private ?AbstractProcessor $tested = null;
//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 
	protected function setUp()
	{

		$client = static::createClient();
		$container = $client->getContainer();
//		$container->set(
//			ServiceExtractorInterface::class,
//			new class ($container) implements ServiceExtractorInterface
//			{
//				private ContainerInterface $container;
//				public function __construct(ContainerInterface $container)
//				{
//					$this->container = $container;
//				}
//				public function get($id)
//				{
//					return $this->container->get($id);
//				}
//
//				public function has($id): bool
//				{
//					return $this->container->has($id);
//
//				}
//
//			}
//		);
		$this->tested = $this->getMockForAbstractClass(
			AbstractProcessor::class,
			[
				$container->get(EntitySaverFactory::class),
				$container->get(EntityRemoverFactory::class),
				$container->get(DtoCreatorFactory::class)
			]

		);
	}
//endregion Protected

//region SECTION: Private

//endregion Private

//region SECTION: Public
	public function testClass()
	{
		$a = 5;
		$b = 6 - 1;
		$this->assertEquals($a, $b, 'NOT EQ');
	}
//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
}