<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityProcessorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param EntityProcessorInterface $processor
     * @param callable            $getProcessedItems
     * @param                     $context
     */
    function setNext(EntityProcessorInterface $processor, callable $getProcessedItems, $context = null): void;

    /**
     * @param EntityProcessorInterface $processor
     */
    function removeNext(EntityProcessorInterface $processor): void;
//endregion Getters/Setters
}