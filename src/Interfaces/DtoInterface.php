<?php

namespace Demoniqus\EntityProcessor\Interfaces;


interface DtoInterface
{
//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
	/**
	 * @return EntityInterface|null
	 */
	function getEntity();

	/**
	 * @return int|null
	 */
	function getId();

	/**
	 * @param EntityInterface|null $entity
	 * @return DtoInterface
	 */
	function setEntity($entity);

	/**
	 * @param int|null $id
	 * @return DtoInterface
	 */
	function setId($id);
//endregion Getters/Setters
}