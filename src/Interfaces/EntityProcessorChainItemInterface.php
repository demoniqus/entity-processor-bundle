<?php


namespace Demoniqus\EntityProcessor\Interfaces;


use Demoniqus\EntityProcessor\Processor\AbstractProcessor;

interface EntityProcessorChainItemInterface
{
//region SECTION:Public
	function getProcessor(): EntityProcessorInterface;

    function isProcessor(AbstractProcessor $processor): bool;
//endregion Public
//region SECTION: Getters/Setters

//endregion Getters/Setters
}