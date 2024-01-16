<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityRemoverFactoryInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    function getRemover(string $removerClassName): EntityRemoverInterface;

    /**
     * @param string[] $removerClassNames
     * @return EntityRemoverInterface[]
     */
    function getRemovers(array $removerClassNames): array;
//endregion Getters/Setters
}