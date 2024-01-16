<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface ChangesDetectorInterface
{
//region SECTION:Public
    function isChange(?EntityInterface $entity, ?DtoInterface $dto, array $properties, bool $inverseProperties = false): bool;
//endregion Public
//region SECTION: Getters/Setters

//endregion Getters/Setters
}