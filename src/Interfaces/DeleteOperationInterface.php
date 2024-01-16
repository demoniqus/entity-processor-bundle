<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DeleteOperationInterface
{
//region SECTION: Fields
    const START_POINT = 'startPoint';
//endregion Fields
//region SECTION:Public

    function extends(): DeleteOperationInterface;

    function addIdentifier(string $identifier, $value): DeleteOperationInterface;
//endregion Public
//region SECTION: Getters/Setters

    function applyToEntity(EntityInterface $entity): void;

    /**
     * @return mixed
     */
    function getId();
    function getIdentifier(string $identifier);

    function getIdentifiers(): array;
//endregion Getters/Setters
}