<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface DelOpManagerInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @return mixed
     */
    function createOperationId();

    function bindDeleteOperationToEntity(
        EntityInterface $entity,
        DeleteOperationInterface $delOp
    );
//endregion Getters/Setters
}