<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityProcessorMetadataInterface
{
//region SECTION:Public
    function createNewLayer($caller): EntityProcessorMetadataInterface;
//endregion Public
//region SECTION: Getters/Setters
    public function setTransacted(bool $isTransacted, bool $thisLayer = false): EntityProcessorMetadataInterface;

    public function isTransacted(bool $thisLayer = false): bool;

    public function isParentCaller(string $className): bool;
//endregion Getters/Setters
}