<?php


namespace Demoniqus\EntityProcessor\Interfaces;


use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;

interface EntityProcessorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param AbstractEntitySaver $processor
     * @param callable            $getProcessedItems
     * @param                     $context
     */
    function setNext(AbstractProcessor $processor, callable $getProcessedItems, $context = null): void;

    /**
     * @param AbstractEntitySaver $processor
     */
    function removeNext(AbstractProcessor $processor): void;
//endregion Getters/Setters
}