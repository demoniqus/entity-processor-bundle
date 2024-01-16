<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DtoCreatorFactoryInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    public function getDtoFromEntity(EntityInterface $entity): DtoInterface;
//endregion Getters/Setters
}