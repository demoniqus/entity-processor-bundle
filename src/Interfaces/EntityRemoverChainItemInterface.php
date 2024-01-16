<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityRemoverChainItemInterface extends EntityProcessorChainItemInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters

    function getEntities(EntityInterface $entity): array;

    function getProcessor(): EntityRemoverInterface;

//endregion Getters/Setters
}