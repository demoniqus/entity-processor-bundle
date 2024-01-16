<?php


namespace Demoniqus\EntityProcessor\Interfaces;


use Demoniqus\EntityProcessor\Exception\SaverNotFoundException;

interface EntitySaverFactoryInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param string $saverClassName
     * @return EntitySaverInterface
     * @throws SaverNotFoundException
     */
    function getSaver(string $saverClassName): EntitySaverInterface;

    /**
     * @param string[] $saverClassNames
     * @return EntitySaverInterface[]
     * @throws SaverNotFoundException
     */
    function getSavers(array $saverClassNames): array;
//endregion Getters/Setters
}