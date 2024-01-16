<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface ChangesDetectorComparatorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param $original - original value to comparing
     * @param $current - current value to comparing
     * @return bool
     */
    function isChange($original, $current): bool;
//endregion Getters/Setters
}