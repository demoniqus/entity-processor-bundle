<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DtoCreatorInterface
{
//region SECTION:Public
    public function fromEntity(EntityInterface $entity): DtoInterface;
//endregion Public
//region SECTION: Getters/Setters

//endregion Getters/Setters
}