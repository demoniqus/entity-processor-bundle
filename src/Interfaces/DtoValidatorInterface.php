<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DtoValidatorInterface extends ValidatorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param DtoInterface                   $dto
     * @param ProcessorOptionsInterface|null $processorOptions
     */
    public function validateDto(DtoInterface $dto, ?ProcessorOptionsInterface $processorOptions = null): void;
//endregion Getters/Setters
}