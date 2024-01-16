<?php

namespace Demoniqus\EntityProcessor\Interfaces;

interface ErrorSubscriberInterface
{
//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
	/**
	 * @param $message
	 * @param $identifier
	 * @param $key
	 * @return ErrorSubscriberInterface
	 */
	function addError($message, $identifier = null, $key = null);
//endregion Getters/Setters
}