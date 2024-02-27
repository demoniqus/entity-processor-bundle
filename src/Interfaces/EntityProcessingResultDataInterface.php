<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityProcessingResultDataInterface
{
    //region SECTION:Public
    public function throwIsWrong(?string $exceptionClassName = \Exception::class, ?string $glue = ', ');
    //endregion Public
    //region SECTION: Getters/Setters
    public function getErrors(): array;

    public function hasErrors(): bool;

    /**
     * @return DtoInterface|DtoInterface[]
     */
    public function getInput();

    /**
     * @return EntityInterface|EntityInterface[]
     */
    public function getResult();

    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $entityClass): bool;
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     * @return bool
     */
    public function isEntityCreated(EntityInterface $entity, string $entityClass): bool;
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     * @return bool
     */
    public function isEntityDeleted(EntityInterface $entity, string $entityClass): bool;
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     * @return array
     */
    public function getEntityChanges(EntityInterface $entity, string $entityClass): array;

    public function getEntityChange(EntityInterface $entity, string $entityClass, string $entityField): array;

    /**
     * @param string $optionName
     * @param        $object
     * @return mixed
     */
    public function getOption(string $optionName, $object = null);

    function getOptions(): ?ProcessorOptionsInterface;
    //endregion Getters/Setters
}
