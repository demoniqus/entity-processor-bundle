<?php

namespace Demoniqus\EntityProcessor\Tests\Dummy\Processor\Saver;

use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;

class TestSaver extends AbstractEntitySaver
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
	protected function isCreating(EntityInterface $entity): bool
	{
		// TODO: Implement isCreating() method.
	}

	protected function isUpdating(EntityInterface $entity): bool
	{
		// TODO: Implement isUpdating() method.
	}

	protected function saveDtoToEntity(DtoInterface $dto): ?EntityInterface
	{
		// TODO: Implement saveDtoToEntity() method.
	}

	protected function recalculateAfterDtoSaving(array $entitiesSet, array $dtosSet, EntityProcessingResultDataInterface $saverResultData, EntityProcessorMetadataInterface $processorMetadata): bool
	{
		// TODO: Implement recalculateAfterDtoSaving() method.
	}

	protected function persist(EntityInterface $entity): void
	{
		// TODO: Implement persist() method.
	}

	protected function detectEntityChanges(EntityInterface $entity, PreserveEntityProcessingResultDataInterface $saverResultData): array
	{
		// TODO: Implement detectEntityChanges() method.
	}

	protected function getEntityClass(): string
	{
		// TODO: Implement getEntityClass() method.
	}

	protected function beginTransaction(): void
	{
		// TODO: Implement beginTransaction() method.
	}

	protected function commit(): void
	{
		// TODO: Implement commit() method.
	}

	protected function rollback(): void
	{
		// TODO: Implement rollback() method.
	}

	protected function flush(): void
	{
		// TODO: Implement flush() method.
	}

	protected function findEntity(?int $id): ?EntityInterface
	{
		// TODO: Implement findEntity() method.
	}
}