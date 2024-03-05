<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityProcessorChainItemInterface
{
//region SECTION:Public
	function getProcessor(): EntityProcessorInterface;

    function isProcessor(EntityProcessorInterface $processor): bool;
//endregion Public
//region SECTION: Getters/Setters

//endregion Getters/Setters
}