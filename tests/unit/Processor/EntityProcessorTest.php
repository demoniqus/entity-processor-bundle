<?php

namespace Demoniqus\EntityProcessor\Tests\unit\Processor;

use Demoniqus\EntityProcessor\Chain\AbstractEntityProcessorChainItem;
use Demoniqus\EntityProcessor\Exception\NextEntityProcessorException;
use Demoniqus\EntityProcessor\Factory\DtoCreatorFactory;
use Demoniqus\EntityProcessor\Factory\EntityRemoverFactory;
use Demoniqus\EntityProcessor\Factory\EntitySaverFactory;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\ProcessingResultData\Preserve\ProcessingResultData as PreserveProcessingResultData;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;
use Demoniqus\EntityProcessor\Tests\WebTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EntityProcessorTest extends WebTestCase
{
//region SECTION: Fields
	/**
	 * @var MockObject|AbstractProcessor|(AbstractProcessor&MockObject)|null
	 */
	private ?MockObject $tested = null;
//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 
	protected function setUp()
	{
		$client = static::createClient();
		$container = $client->getContainer();
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


	public function testProcessingNextProcessorResult()
	{
		$container = (static::createClient())->getContainer();

		$dummyProcessor = new class (
			$container->get(EntitySaverFactory::class),
			$container->get(EntityRemoverFactory::class),
			$container->get(DtoCreatorFactory::class)
		) extends AbstractEntitySaver {

			protected function isCreating(EntityInterface $entity): bool
			{
				return !$entity->getId();
			}

			protected function isUpdating(EntityInterface $entity): bool
			{
				return !!$entity->getId();
			}

			protected function saveDtoToEntity(DtoInterface $dto): ?EntityInterface
			{
				return $dto->getEntity();
			}

			protected function recalculateAfterDtoSaving(array $entitiesSet, array $dtosSet, EntityProcessingResultDataInterface $saverResultData, EntityProcessorMetadataInterface $processorMetadata): bool
			{
				return true;
			}

			protected function persist(EntityInterface $entity): void
			{

			}

			protected function detectEntityChanges(EntityInterface $entity, PreserveEntityProcessingResultDataInterface $saverResultData): array
			{
				return [];
			}

			protected function getEntityClass(): string
			{
				return __CLASS__;
			}

			protected function beginTransaction(): void{}

			protected function commit(): void{}

			protected function rollback(): void{}

			protected function flush(): void{}

			protected function findEntity(?int $id): ?EntityInterface
			{
				return null;
			}
		};

		$chainItem = $this->getMockForAbstractClass(
			AbstractEntityProcessorChainItem::class,
			[
				$dummyProcessor,
				null
			]
		);
		$errorSubscriber = new PreserveProcessingResultData();
		$nextProcessorResult = new PreserveProcessingResultData();
		$nextProcessorResult->addError('Error');

		$this->expectException(NextEntityProcessorException::class);
		\Closure::bind(
			function() use ($chainItem, $errorSubscriber, $nextProcessorResult) {
				$this->processNextProcessorResult($chainItem, $nextProcessorResult, $errorSubscriber);
			},
			$this->tested,
			AbstractProcessor::class
		)->__invoke();
	}
//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
}