<?php

namespace Demoniqus\EntityProcessor\Interfaces;

interface EntityClassExtractorInterface
{
//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters 
	function getClass(EntityInterface $entity): string;
//endregion Getters/Setters
}