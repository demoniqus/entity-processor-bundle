<?php


namespace Demoniqus\EntityProcessor\Interfaces\Preserve;

use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface as ReadonlyEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\ErrorSubscriberInterface;

/**
 * @internal used only into EntitySaver. use Demoniqus\EntityProcessor\Interfaces\EntitySaverTransactionDataInterface to access saver's resulting data
 */
interface EntityProcessingResultDataInterface extends ErrorSubscriberInterface
{
    //region SECTION:Public
    function inheritErrors(ReadonlyEntityProcessingResultDataInterface $source): void;
    function finalize(): ReadonlyEntityProcessingResultDataInterface;
    //endregion Public
    //region SECTION: Getters/Setters
    function setResult($result): void;
    function setEntityAsCreated(EntityInterface $entity, string $entityClass): void;
    function setEntityAsUpdated(EntityInterface $entity, string $entityClass): void;
    function setEntityAsDeleted(EntityInterface $entity, string $entityClass): void;
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass Любая сущность характеризуется двумя параметрами - идентификатором и классом.
     *                                     EntitySaver в принципе работает с сущностью одного класса и поэтому нет острой
     *                                     необходимости сохранять информацию об изменениях с указанием класса, однако
     *                                     на всякий случай оставлю полную идентификацию.
     * @param array           $changeSet
     */
    function addChangeSet(EntityInterface $entity, string $entityClass, array $changeSet): void;

    function addEntityChanging(EntityInterface $entity, string $entityClass, array $changeSet): void;
    //endregion Getters/Setters
}
