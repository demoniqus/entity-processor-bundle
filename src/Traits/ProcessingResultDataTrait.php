<?php


namespace Demoniqus\EntityProcessor\Traits;


use Demoniqus\EntityProcessor\Interfaces\EntityInterface;

/**
 * @property array $processingTypes
 */
trait ProcessingResultDataTrait
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public
    public function throwIsWrong(?string $exceptionClassName = \Exception::class, ?string $glue = ', ')
    {
        if (!count($this->getErrors())) {
            return;
        }
        throw new $exceptionClassName(
            implode(
                $glue,
                array_map(
                    function($error){return (string) $error;},
                    $this->getErrors()
                )
            )
        );
    }
//endregion Public

//region SECTION: Getters/Setters
    public function isEntityCreated(EntityInterface $entity, string $entityClass): bool
    {
        return ($this->processingTypes[$entityClass][$entity->getId()] ?? null) === 'created';
    }
    public function isEntityUpdated(EntityInterface $entity, string $entityClass): bool
    {
        return ($this->processingTypes[$entityClass][$entity->getId()] ?? null) === 'updated';
    }
    public function isEntityDeleted(EntityInterface $entity, string $entityClass): bool
    {
        return ($this->processingTypes[$entityClass][$entity->getId()] ?? null) === 'deleted';
    }

//endregion Getters/Setters
}