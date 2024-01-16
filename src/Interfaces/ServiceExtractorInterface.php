<?php

namespace Demoniqus\EntityProcessor\Interfaces;

interface ServiceExtractorInterface
{
//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
	/**
	 * @param $id
	 * @return null|object
	 */
	function get($id);

	function has($id): bool;
//endregion Getters/Setters
}