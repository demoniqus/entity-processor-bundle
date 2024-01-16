<?php

namespace Demoniqus\EntityProcessor\Utils;

use Demoniqus\EntityProcessor\Interfaces\EntityClassExtractorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;

final class EntityClassExtractor implements EntityClassExtractorInterface
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters 
	public function getClass(EntityInterface $entity): string
	{
		//Doctrine\Common\Util\ClassUtils::getRealClass(get_class($entity));
		return get_class($entity);
	}
//endregion Getters/Setters
}