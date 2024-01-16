<?php


namespace Demoniqus\EntityProcessor\Interfaces\Preserve;


use Demoniqus\EntityProcessor\Interfaces\EntityInterface;

interface RefDelOpIdInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param EntityInterface|null $delOp
     * @return $this
     * @noinspection PhpMissingReturnTypeInspection
     */
    function setDelOp(?EntityInterface $delOp);
//endregion Getters/Setters
}