<?php


namespace Demoniqus\EntityProcessor\Interfaces;



interface EntitySaverChainItemInterface extends EntityProcessorChainItemInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param DtoInterface|null    $dto - null в случае, если saver вызывается в цепочке после remover'а
     * @param EntityInterface|null $entity - null в случае, если saver вызывается в цепочке после remover'а
     * @return array
     */
    function getDtos(?DtoInterface $dto, ?EntityInterface $entity): array;

    function getProcessor(): EntitySaverInterface;

//endregion Getters/Setters
}