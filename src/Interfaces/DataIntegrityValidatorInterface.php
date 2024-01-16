<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DataIntegrityValidatorInterface extends ValidatorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param EntityInterface[]              $entitiesSet
     * @param DtoInterface[]                 $dtosSet
     * @param ProcessorOptionsInterface|null $processorOptions
     * @return void
     */
    public function validateData(array $entitiesSet, ?array $dtosSet = null, ?ProcessorOptionsInterface $processorOptions = null): void;

//endregion Getters/Setters
}