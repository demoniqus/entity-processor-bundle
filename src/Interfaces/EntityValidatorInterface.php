<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityValidatorInterface extends ValidatorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param EntityInterface                $entity
     * @param DtoInterface|null              $dto
     * @param ProcessorOptionsInterface|null $processorOptions
     */
    public function validateEntity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void;

//endregion Getters/Setters
}