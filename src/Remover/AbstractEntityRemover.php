<?php


namespace Demoniqus\EntityProcessor\Remover;


use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\DtoSavingFailedException;
use Demoniqus\EntityProcessor\Exception\DtoValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\EntityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;
use Demoniqus\EntityProcessor\Exception\SaverNotFoundException;
use Demoniqus\EntityProcessor\Interfaces\DataIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoCreatorFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\ErrorSubscriberInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\Interfaces\ValidatorInterface;
use Demoniqus\EntityProcessor\ProcessingResultData\Preserve\ProcessingResultData;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\ProcessorOptions\ProcessorOptions;
use Throwable;

abstract class AbstractEntityRemover extends AbstractProcessor implements EntityRemoverInterface
{
//region SECTION: Fields
    private array $validators = [
        'beforeDelete' => [],
        'afterDelete' => [],
        'dataIntegrity' => [],
    ];

//endregion Fields

//region SECTION: Constructor
    public function __construct(
        EntitySaverFactoryInterface $entitySaverFactory,
        EntityRemoverFactoryInterface $entityRemoverFactory,
        DtoCreatorFactoryInterface $dtoCreatorFactory
    )
    {
        parent::__construct($entitySaverFactory, $entityRemoverFactory, $dtoCreatorFactory);
    }
//endregion Constructor

//region SECTION: Protected
    abstract protected function deleteEntity(EntityInterface $entity): bool;

    /**
     * Удаляемая сущность может иметь неких потомков, которые необходимо удалить вместе с этой сущностью.
     * Также на удаляемую сущность могут ссылаться другие сущности. В этом случае связь надо разорвать, а сущность пересчитать.
     * Потомки тоже могут иметь какие-то дополнительные внешние связи, которые надо разрывать при их удалении и пересчитывать.
     *
     * @param array                               $entitiesSet
     * @param EntityProcessingResultDataInterface $removerResultData
     * @param EntityProcessorMetadataInterface    $processorMetadata
     * @return bool
     */
    abstract protected function recalculateAfterDeleting(array $entitiesSet, EntityProcessingResultDataInterface $removerResultData, EntityProcessorMetadataInterface $processorMetadata): bool;

//endregion Protected

//region SECTION: Private
    /**
     * @param EntityInterface          $entity
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool
     */
    final private function validateBeforeDelete(EntityInterface $entity, ErrorSubscriberInterface $errorSubscriber): bool
    {
        /** @var EntityValidatorInterface $validator */
        foreach ($this->validators['entity'] as $validator) {
            $validator->addErrorSubscriber($errorSubscriber);
            $validator->validateEntity($entity, null, $errorSubscriber->getOptions());
            $validator->rejectErrorSubscriber($errorSubscriber);
        }
        return !$errorSubscriber->hasErrors();
    }

    /**
     * @param EntityInterface          $entity
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool
     */
    final private function validateAfterDelete(EntityInterface $entity, ErrorSubscriberInterface $errorSubscriber): bool
    {
        /** @var EntityIntegrityValidatorInterface $validator */
        foreach ($this->validators['entityIntegrity'] as $validator) {
            $validator->addErrorSubscriber($errorSubscriber);
            $validator->validateEntityIntegrity($entity, null, $errorSubscriber->getOptions());
            $validator->rejectErrorSubscriber($errorSubscriber);
        }
        return !$errorSubscriber->hasErrors();
    }

    /**
     * @param array                    $entitiesSet
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool
     */
    final private function validateDataIntegrity(array $entitiesSet, ErrorSubscriberInterface $errorSubscriber): bool
    {
        /** @var DataIntegrityValidatorInterface $validator */
        foreach ($this->validators['dataIntegrity'] as $validator) {
            $validator->addErrorSubscriber($errorSubscriber);
            $validator->validateData($entitiesSet, null, $errorSubscriber->getOptions());
            $validator->rejectErrorSubscriber($errorSubscriber);
        }
        return !$errorSubscriber->hasErrors();
    }

    /**
     * @param EntityInterface                                                                 $entity
     * @param EntityProcessingResultDataInterface|PreserveEntityProcessingResultDataInterface $removerResultData
     * @param EntityProcessorMetadataInterface                                                $processorMetadata
     * @return bool
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityIntegrityValidationFailedException
     * @throws EntityProcessorException
     * @throws EntityValidationFailedException
     * @throws FinalRecalculationFailedException
     * @throws Throwable
     */
    final private function deleteSingleEntity(
        EntityInterface $entity,
        PreserveEntityProcessingResultDataInterface $removerResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): bool
    {
        if ($this->validateBeforeDelete($entity, $removerResultData) === false || $removerResultData->hasErrors()) {
            throw new EntityValidationFailedException('Невозможно выполнить удаление.');
        }
        if ($this->deleteEntity($entity) === false)
        {
            /**
             * Статус сущности не изменился. Поэтому и рассчитывать в ней дальше нечего. Возможно,
             * что она была удалена ранее.
             * В EntitySaver'е изменения в сущности определялись через EntityManager. И при наличии изменений проводились
             * те или иные расчеты. Здесь будем ориентироваться по результату вызова метода deleteEntity:
             * - true - метод изменил состояние сущности и нужно проводить расчеты
             * - false - метод не изменил состояние сущности и расчеты проводить не требуется.
             * Каким образом мы можем попасть в повторное удаление сущности? Например, при удалении документа DI нужно
             * удалять все связанные с ним DD. Но при этом для определенных типов документов, если удалены все
             * связанные с ними DD, нужно гарантированно удалить и сам документ, т.е. при удалении последнего DD должен
             * быть вызван Remover для документа, который как раз и увидит, что документ уже удален и не замкнет рекурсию.
             */
            return false;
        }

        $className = $this->getEntityClass();
        $removerResultData->setEntityAsDeleted($entity, $className);

        /**
         * Чтобы для дальнейших расчетов можно было делать запросы к БД и получать корректные данные,
         * обновим базу
         */
        $this->flush();
        if ($this->validateAfterDelete($entity, $removerResultData) === false || $removerResultData->hasErrors()) {
            throw new EntityIntegrityValidationFailedException('Вносимые изменения нарушают целостность данных.');
        }

        $this->callNextProcessors(null, $entity, $removerResultData, $processorMetadata);
        return true;
    }

    /**
     * @param EntityInterface[]                                                               $entitiesSet
     * @param EntityProcessingResultDataInterface|PreserveEntityProcessingResultDataInterface $removerResultData
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityIntegrityValidationFailedException
     * @throws EntityProcessorException
     * @throws FinalRecalculationFailedException
     * @throws SaverNotFoundException
     * @throws Throwable
     */
    final private function finalize(
        array $entitiesSet,
        PreserveEntityProcessingResultDataInterface $removerResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): void
    {
        if ($this->recalculateAfterDeleting($entitiesSet, $removerResultData, $processorMetadata) === false) {
            throw new FinalRecalculationFailedException('Ошибка при выполнении пересчета всех данных с учетом внесенных изменений.');
        }

        if ($this->validateDataIntegrity($entitiesSet, $removerResultData) === false) {
            throw new DataIntegrityValidationException('Ошибка при выполнении проверки целостности данных с учетом внесенных изменений.');
        }

        if ($removerResultData->hasErrors()) {
            throw new EntityProcessorException();
        }
    }


//endregion Private
//region SECTION: Public
    /**
     * @param EntityInterface                       $entity
     * @param ProcessorOptionsInterface|null        $options
     * @param EntityProcessorMetadataInterface|null $processorMetadata
     * @return EntityProcessingResultDataInterface
     * @throws Throwable
     */
    public function delete(
        EntityInterface $entity,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface
    {
        $processorMetadata = $this->getProcessorMetadata($processorMetadata);
        $options = $options ?? new ProcessorOptions();

        /**
         * Не нужно заводить таблицу с отдельной сущностью "Операция удаления".
         * Можно сделать отдельное поле-хеш операции удаления.
         * В первом случае теоретически можно достаточно просто восстановить удаленные данные,
         * но это формирует лишние связи.
         * Во втором случае поле является обычным текстом и восстанавливать данные придется вручную.
         */
        $resultData = new ProcessingResultData(null, $options);

        if (!$processorMetadata->isTransacted()) {
            $processorMetadata
                ->setTransacted(true)
                ->setTransacted(true, true);
            $this->beginTransaction();
        }

        try {
            if ($this->deleteSingleEntity($entity, $resultData, $processorMetadata)) {
                /**
                 * Финальные расчеты имеют смысл только в том случае, если изменился статус сущности с активного на
                 * удаленный
                 */
                $this->finalize([$entity], $resultData, $processorMetadata);
            }
        } catch (Throwable $ex) {
            return $this->processException($ex, $resultData, $processorMetadata);
        }

        return $this->processResult(null, $resultData, $processorMetadata);
    }

    /**
     * @param EntityInterface[]                     $entities
     * @param ProcessorOptionsInterface|null        $options
     * @param EntityProcessorMetadataInterface|null $processorMetadata
     * @return EntityProcessingResultDataInterface
     * @throws Throwable
     */
    public function deleteSet(
        array $entities,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface
    {
        $processorMetadata = $this->getProcessorMetadata($processorMetadata);
        $options = $options ?? new ProcessorOptions();

        $removerResultData = new ProcessingResultData(null, $options);
        if (!count($entities)) {
            return $removerResultData;
        }

        if (!$processorMetadata->isTransacted()) {
            $processorMetadata
                ->setTransacted(true)
                ->setTransacted(true, true);
            $this->beginTransaction();
        }

        try {
            $changedEntities = [];
            foreach ($entities as $entity) {
                if ($this->deleteSingleEntity($entity, $removerResultData, $processorMetadata))
                {
                    /**
                     * В EntitySaver'е финальные пересчеты вызываются соответственно произведенным в сущности изменениям,
                     * которые вычисляются с помощью EntityManager'а в методе recalculateAfterDtoSaving. В EntityRemover'е
                     * мы сразу определяем список изменившихся сущностей и только для них вызываем пересчеты. В противном
                     * случае возникнет бесконечная рекурсия.
                     */
                    $changedEntities[] = $entity;
                }
            }
            if (count($changedEntities)) {
                $this->finalize($changedEntities, $removerResultData, $processorMetadata);
            }
        } catch (Throwable $ex) {
            return $this->processException($ex, $removerResultData, $processorMetadata);
        }

        return $this->processResult( null, $removerResultData, $processorMetadata);
    }

    /**
     * @param iterable $validators
     * @throws EntityProcessorException
     * @noinspection PhpUnused
     */
    public function addValidators(iterable $validators): void
    {
        foreach ($validators as $validator) {
            if (!($validator instanceof ValidatorInterface)) {
                throw new EntityProcessorException('Недопустимый класс проверки данных. Обратитесь к разработчику.');
            }
            if ($validator instanceof EntityValidatorInterface) {
                $this->validators['entity'][spl_object_hash($validator)] = $validator;
            }
            if ($validator instanceof EntityIntegrityValidatorInterface) {
                $this->validators['entityIntegrity'][spl_object_hash($validator)] = $validator;
            }
            if ($validator instanceof DataIntegrityValidatorInterface) {
                $this->validators['data'][spl_object_hash($validator)] = $validator;
            }
        }
    }
//endregion Public

//region SECTION: Getters/Setters

//endregion Getters/Setters
}