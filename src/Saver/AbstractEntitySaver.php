<?php


namespace Demoniqus\EntityProcessor\Saver;

use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\DtoSavingFailedException;
use Demoniqus\EntityProcessor\Exception\DtoValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\EntityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;
use Demoniqus\EntityProcessor\Interfaces\DataIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\ErrorSubscriberInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\Interfaces\ValidatorInterface;
use Demoniqus\EntityProcessor\ProcessingResultData\Preserve\ProcessingResultData;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\ProcessorOptions\ProcessorOptions;
use Throwable;

/**
 * EntitySaver предназначен для реализации функционала сохранения некоторой сущности и должен гарантировать единый
 * алгоритм сохранения сущности в любой точке кода.
 *
 * Сохранение любой сущности состоит из нескольких общих этапов:
 * - заполнение Dto из запроса - данный этап выполняется вне EntitySaver
 * - валидация Dto - этот и следующие этапы выполняются с помощью EntitySaver
 * - перенос данных из Dto в сущность
 * - проверка, можно ли вообще редактировать выбранную сущность
 * - фиксация всех изменений сущностей - эти изменения могут напрямую влиять на дальнейшие расчеты
 * - предварительная проверка целостности и допустимости всех данных с учетом нового состояния сущности
 * - вызов связанных saver'ов
 * - пересчет всех данных с учетом нового состояния сущности - в случае работы с набором сущностей этот этап
 *      выполняется однократно после обновления всех сущностей
 * - сохранение рассчитанных изменений - в случае работы с набором сущностей этот этап выполняется однократно
 *      после обновления всех сущностей
 * - итоговая проверка целостности и допустимости всех данных с учетом нового состояния сущности - в случае работы с
 * набором сущностей этот этап выполняется однократно после обновления всех сущностей
 */
abstract class AbstractEntitySaver extends AbstractProcessor implements EntitySaverInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private array $validators = [
        self::VALIDATOR_CATEGORY_DTO => [],
        self::VALIDATOR_CATEGORY_ENTITY => [],
        self::VALIDATOR_CATEGORY_ENTITY_INTEGRITY => [],
        self::VALIDATOR_CATEGORY_DATA_INTEGRITY => [],
    ];


    //endregion Fields

    //region SECTION: Constructor

    //endregion Constructor

    //region SECTION: Protected
    /**
     * @param EntityInterface                             $entity
     * @param DtoInterface                                $dto
     * @param EntityProcessingResultDataInterface|PreserveEntityProcessingResultDataInterface $saverResultData
     * @param EntityProcessorMetadataInterface            $processorMetadata
     * @param array                                       $changeSet
     * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
    protected function isMustFixed(
        EntityInterface $entity,
        DtoInterface $dto,
        EntityProcessingResultDataInterface $saverResultData,
        EntityProcessorMetadataInterface $processorMetadata,
        array $changeSet
    ): bool
    {
        /**
         * В некоторых случаях мы можем точно сказать, изменилась ли сущность или нет, надо ли в связи с этим
         * фиксировать ее в БД или нет, а также выполнять какие-то дополнительные действия, связанные с этими
         * изменениями или не надо.
         * С методом надо быть осторожнее, т.к. незафиксированные изменения могут дать в дальнейшем неверные выборки данных,
         * хотя сама сущность может содержать в себе корректные данные.
         */
        return true;
    }

	/**
	 * @param DtoInterface $dto
	 * @return EntityInterface
	 * @noinspection PhpUnused
	 */
	protected function getEntityFromDto(DtoInterface $dto): EntityInterface
	{
		if ($dto->getEntity()) {
			return $dto->getEntity();
		}
		if ($dto->getId()) {
			return $this->findEntity($dto->getId());
		}
		$entityClassName = $this->getEntityClass();
		$entity = new $entityClassName;
		$this->persist($entity);

		return $entity;
	}

	/**
	 * Метод определяет, является ли обрабатываемая сущность вновь созданной, а не измененной
	 * @param EntityInterface $entity
	 * @return bool
	 */
	abstract protected function isCreating(
		EntityInterface $entity
	): bool;

	/**
	 * Метод определяет, является ли обрабатываемая сущность измененной, а не вновь созданной
	 * @param EntityInterface $entity
	 * @return bool
	 */
	abstract protected function isUpdating(
		EntityInterface $entity
	): bool;

    /**
     * Метод обеспечивает перенос обновленных данных из Dto в сущность
     *
     * @param DtoInterface $dto
     * @return EntityInterface|null - entity on success or null or instance of \Exception on fail
     */
    abstract protected function saveDtoToEntity(DtoInterface $dto): ?EntityInterface;

    /**
     * Метод обеспечивает пересчет всех данных с учетом нового состояния сущности.
     * Метод может вызывать другие EntitySaver'ы. Отличие от использования цепочек EntitySaver'ов состоит в следующем:
     * при создании BudgetItemData всегда возникает связанный DistributionData и поэтому его EntitySaver следует
     * вызывать в данном методе, однако BudgetItemData типа estimate одними источниками может предоставляться без
     * связанных выполнений, а в других - вместе со связанными выполнениями. Такая ситуация может быть решена
     * использованием цепочек EntitySaver'ов
     *
     * @param array|null                          $entitiesSet
     * @param array|null                          $dtosSet
     * @param EntityProcessingResultDataInterface $saverResultData
     * @param EntityProcessorMetadataInterface    $processorMetadata
     * @return bool - true on success or false or instance of \Exception on fail
     */
    abstract protected function recalculateAfterDtoSaving(
        array $entitiesSet,
        array $dtosSet,
        EntityProcessingResultDataInterface $saverResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): bool;

	abstract protected function persist(EntityInterface $entity): void;

	/**
	 * @param EntityInterface                             $entity
	 * @param PreserveEntityProcessingResultDataInterface $saverResultData
	 * @return array
	 */
	abstract protected function detectEntityChanges(EntityInterface $entity, PreserveEntityProcessingResultDataInterface $saverResultData): array;

    //endregion Protected

    //region SECTION: Private
    /**
     * Валидация данных в Dto. Выполняется только проверка корректности данных внутри Dto. Метод не предназначается для
     * проверки целостности и допустимости всех данных с учетом нового состояния сущности
     *
     * @param DtoInterface                                                 $dto
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool - true on success and false or instance of \Exception on fail
     */
    final private function validateDto(DtoInterface $dto, ErrorSubscriberInterface $errorSubscriber): bool
    {
        if ($errorSubscriber->getOption(EntitySaverInterface::SKIP_DTO_VALIDATION, $this) === true) {
            return true;
        }
        /** @var DtoValidatorInterface $dtoValidator */
        foreach ($this->validators[self::VALIDATOR_CATEGORY_DTO] as $dtoValidator) {
            $dtoValidator->addErrorSubscriber($errorSubscriber);
            $dtoValidator->validateDto($dto, $errorSubscriber->getOptions());
            $dtoValidator->rejectErrorSubscriber($errorSubscriber);
        }

        return !$errorSubscriber->hasErrors();
    }

    /**
     * Метод обеспечивает проверку, возможно ли вообще редактирование указанной сущности. В этот момент все данные из
     * Dto перенесены в сущность. Возможно, вычислены какие-то связанные данные.
     *
     * @param EntityInterface                                              $entity
     * @param DtoInterface                                                 $dto - теоретически $dto может хранить какие-то
     *                                                                     дополнительные данные, относящиеся к $entity
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool - true on success or false or instance of \Exception on fail
     */
    final private function validateEntity(EntityInterface $entity, DtoInterface $dto, ErrorSubscriberInterface $errorSubscriber): bool
    {
        if ($errorSubscriber->getOption(EntitySaverInterface::SKIP_ENTITY_VALIDATION, $this) === true) {
            return true;
        }
        /** @var EntityValidatorInterface $entityValidator */
        foreach ($this->validators[self::VALIDATOR_CATEGORY_ENTITY] as $entityValidator) {
            $entityValidator->addErrorSubscriber($errorSubscriber);
            $entityValidator->validateEntity($entity, $dto, $errorSubscriber->getOptions());
            $entityValidator->rejectErrorSubscriber($errorSubscriber);
        }

        return !$errorSubscriber->hasErrors();
    }
    /**
     * Метод обеспечивает предварительную проверку целостности и допустимости всех данных с учетом нового состояния
     * сущности
     *
     * @param EntityInterface                                              $entity
     * @param DtoInterface                                                 $dto - теоретически $dto может хранить какие-то
     *                                                                     дополнительные данные, относящиеся к $entity
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool - true on success or false or instance of \Exception on fail
     */
    final private function validateEntityIntegrity(EntityInterface $entity, DtoInterface $dto, ErrorSubscriberInterface $errorSubscriber): bool
    {
        /** @var EntityIntegrityValidatorInterface $entityIntegrityValidator */
        foreach ($this->validators[self::VALIDATOR_CATEGORY_ENTITY_INTEGRITY] as $entityIntegrityValidator) {
            $entityIntegrityValidator->addErrorSubscriber($errorSubscriber);
            $entityIntegrityValidator->validateEntityIntegrity($entity, $dto, $errorSubscriber->getOptions());
            $entityIntegrityValidator->rejectErrorSubscriber($errorSubscriber);
        }

        return !$errorSubscriber->hasErrors();
    }

    /**
     * Метод обеспечивает итоговую проверку целостности и допустимости всех данных после пересчета данных с учетом
     * нового состояния сущности
     *
     * @param EntityInterface[]                                            $entitiesSet
     * @param ErrorSubscriberInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @return bool - true on success or false or instance of \Exception on fail
     */
    final private function validateDataIntegrity(array $entitiesSet, array $dtosSet, ErrorSubscriberInterface $errorSubscriber): bool
    {
        /** @var DataIntegrityValidatorInterface $dataIntegrityValidator */
        foreach ($this->validators[self::VALIDATOR_CATEGORY_DATA_INTEGRITY] as $dataIntegrityValidator) {
            $dataIntegrityValidator->addErrorSubscriber($errorSubscriber);
            $dataIntegrityValidator->validateData($entitiesSet, $dtosSet, $errorSubscriber->getOptions());
            $dataIntegrityValidator->rejectErrorSubscriber($errorSubscriber);
        }

        return !$errorSubscriber->hasErrors();
    }

    /**
     * @param DtoInterface                                                                    $dto
     * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $saverResultData
     * @param EntityProcessorMetadataInterface                                                $processorMetadata
     * @return EntityInterface
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityIntegrityValidationFailedException
     * @throws EntityProcessorException
     * @throws EntityValidationFailedException
     * @throws FinalRecalculationFailedException
     * @throws Throwable
     */
    final private function saveSingleDto(
        DtoInterface $dto,
        PreserveEntityProcessingResultDataInterface $saverResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): EntityInterface
    {
        if ($this->validateDto($dto, $saverResultData) === false || $saverResultData->hasErrors()) {
            throw new DtoValidationFailedException('Данные запроса некорректны.');
        }

        /** @var EntityInterface $entity */
        $entity = $this->saveDtoToEntity($dto);

        if (!$entity) {
            throw new DtoSavingFailedException('Не удалось преобразовать данные из запроса в объект бизнес-логики. Обратитесь к разработчику.');
        }

        if ($this->validateEntity($entity, $dto, $saverResultData) === false || $saverResultData->hasErrors()) {
            throw new EntityValidationFailedException('Данные сохраняемой сущности некорректны.');
        }

        $this->persist($entity);

        list($entityClassName, $changeSet) = $this->detectEntityChanges($entity, $saverResultData);

        $setAsCreated = false;
        if ($this->isCreating($entity)) {
            $setAsCreated = true;
        }
        else if ($this->isUpdating($entity))  {
            $saverResultData->setEntityAsUpdated($entity, $entityClassName);
        }
        /**
         * Данные в dto были проверены на допустимость. Метод saveDtoToEntity должен был сформировать сущность,
         * соответствующую требованиям структуры хранилища данных (заполнены not-null поля, данные имеют правильные форматы
         * и т.п., хотя и могут быть логически недопустимыми, что будет выявлено уже дальнейшими проверками). Теперь
         * необходимо обеспечить, чтобы сущность гарантированно имела ID, если пользователь об этом не позаботился,
         * а также свяжем dto и entity, поскольку в случае сохранения одной сущности пользователь это может сделать сам,
         * но при сохранении набора или при сохранении цепочек сущностей у пользователя нет такой возможности.
         * Кроме того, для выполнения проверки целостности и допустимости всех данных с учетом нового состояния сущности
         * необходимо зафиксировать все изменения в БД, чтобы проверочные методы имели доступ именно к измененным данным.
         */
        try {
            if (
                $this->isMustFixed(
                    $entity,
                    $dto,
                    $saverResultData,
                    $processorMetadata,
                    $changeSet
                )
            ) {
                $this->flush();
            }//TODO Возможно, полученный статус следует складывать в saverResult?
        } catch (Throwable $ex) {
            $saverResultData->addError($ex->getMessage());
        }
        $dto->setId($entity->getId());
        $dto->setEntity($entity);
        if ($setAsCreated) {
            $saverResultData->setEntityAsCreated($entity, $entityClassName);
        }
        /**
         * Фиксируем все изменения сущности, поскольку они напрямую могут определять дальнейшие расчеты на основе обновленных данных
         */
        $saverResultData->addChangeSet($entity, $entityClassName, $changeSet);

        if ($this->validateEntityIntegrity($entity, $dto, $saverResultData) === false || $saverResultData->hasErrors()) {
            throw new EntityIntegrityValidationFailedException('Вносимые изменения нарушают целостность данных.');
        }

        $this->callNextProcessors($dto, $entity, $saverResultData, $processorMetadata);

        return $entity;
    }

	/**
	 * @param array $entities
	 * @param array $dtos
	 * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $saverResultData
	 * @param EntityProcessorMetadataInterface $processorMetadata
	 * @throws DataIntegrityValidationException
	 * @throws EntityProcessorException
	 * @throws FinalRecalculationFailedException
	 */
    final private function finalize(
        array $entities,
        array $dtos,
        PreserveEntityProcessingResultDataInterface $saverResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): void
    {
        if ($this->recalculateAfterDtoSaving($entities, $dtos, $saverResultData, $processorMetadata) === false) {
            throw new FinalRecalculationFailedException('Ошибка при выполнении пересчета всех данных с учетом внесенных изменений.');
        }
        /**
         * На этапе формирования сущности из dto необходимо стараться избегать изменения каких-либо данных - данные должны
         * только переноситься из dto в сущность. Дополнительные расчеты данных как для редактируемой сущности, так и для
         * связанных с нею сущностей следует проводить в методе recalculateAfterDtoSaving. Исключением могут являться те
         * данные, без которых просто невозможно сохранить изменения из-за ограничений со стороны хранилища.
         * Например, без какого-либо значения flags нельзя сохранить этап. Поэтому такие данные могут быть установлены
         * внутри метода saveFromDto.
         * Для прочих изменений и расчетов предназначен метод recalculateAfterDtoSaving. При этом изменения могут быть
         * следующего характера:
         * - изменения редактируемой сущности. Например, может измениться тип формулы, если пользователь сделал в ней
         * первую запись. Подобные изменения сущности возникают только при определенных условиях (т.е. зависят от этих
         * условий) и фактически проверяются сразу в момент расчета. Поэтому такие изменения можно сразу фиксировать в
         * сущность и не вызывать повторно saver для валидации. По крайней мере, если подобные изменения не вызывают
         * дальнейшей сложной реакции. Например, флаг completed можно сразу установить и сохранить в сущности без
         * дополнительных рекурсивных вызовов saver'a, а изменение подрядного договора у сметы подрядчика ведет к сложному
         * изменению дочерних актов подрядчика. Поэтому подобные изменения лучше полноценно прогонять через весь saver.
         * При этом следует помнить, что принципиальная возможность внесения изменений (например, доступность редактирования
         * текущего доходного договора) уже проверена на первой итерации saver'а.
         * - изменения связанных сущностей. Например, при изменении bidBdrContr.contract у него может измениться документ
         * (измениться могут как параметры документа, так и измениться ссылка на документ). Любая сущность может знать
         * лишь свою логику и знать свои связи. За связи своих связей, за их внутреннюю логику сущность не должна отвечать,
         * не должна об этом что-либо знать. Поэтому изменения связанных сущностей должны обрабатываться отдельными
         * saver'ами. При этом может изменяться как отдельная связанная сущность, например, документ, так и сразу целый
         * набор, например, дочерние выполнения сметы. Тут следует понимать следующие аспекты. Пользователь через свой
         * интерфейс может изменять за один раз единственную сущность, набор несвязанных или слабо связанных сущностей (
         * например, выполнения по смете - они связаны между собой сметой и их общая сумма не должна превышать эту смету,
         * но друг о друге эти выполнения не знают), набор связанных сущностей (например смету с ее выполнениями - смета
         * знает о наличии у нее выполнений, а выполнения знают о родительской смете). В таких случаях пользователь вынужден
         * связывать saver'ы между собой, выполнять групповую обработку наборов сущностей, т.к. при ошибке в любой из
         * них необходимо отклонить все предложенные изменения. К тому же, пользователь свои изменения применяет изначально
         * к dto, а не к сущностям. Однако saver изначально работает в рамках собственной транзакции. Поэтому нет
         * необходимости при изменении saver'ом целого набора сущностей выполнять его групповую обработку и создавать
         * цепочки saver'ов. Во-первых, изменения вносятся непосредственно в сущность, а не в dto. Поэтому любой из
         * вызванных дополнительных saver'ов получит доступ сразу ко всем изменениям всех сущностей. Во-вторых, т.к.
         * saver работает изначально в рамках транзакции, то его остановит и заставит отменить все изменения ошибка
         * на любой итерации любого дополнительного saver'а.
         */

        if ($this->validateDataIntegrity($entities, $dtos, $saverResultData) === false) {
            throw new DataIntegrityValidationException('Ошибка при выполнении проверки целостности данных с учетом внесенных изменений.');
        }

        if ($saverResultData->hasErrors()) {
            throw new EntityProcessorException();
        }
    }
    //endregion Private

    //region SECTION: Public
    /**
     * @inheritDoc
     */
    final public function saveSet(
        array $dtos,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface
    {
        $this->init();
        $result = [];
        $options = $options ?? new ProcessorOptions();

        $processorMetadata = $this->getProcessorMetadata($processorMetadata);

        $saverResultData = new ProcessingResultData($dtos, $options);

        if (!count($dtos)) {
            return $saverResultData;
        }

        if (!$processorMetadata->isTransacted()) {
            $processorMetadata
                ->setTransacted(true)
                ->setTransacted(true, true);
            $this->beginTransaction();
        }

        try {
            foreach ($dtos as $dto) {
                $result[] = $this->saveSingleDto($dto, $saverResultData, $processorMetadata);
            }

            $this->finalize($result, $dtos, $saverResultData, $processorMetadata);
        } catch (Throwable $ex) {
            return $this->processException($ex, $saverResultData, $processorMetadata);
        }

        return $this->processResult($result, $saverResultData, $processorMetadata);
    }

    /**
     * @inheritDoc
     */
    final public function save(
        DtoInterface $dto,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface
    {
        $this->init();
        $options = $options ?? new ProcessorOptions();

        $processorMetadata = $this->getProcessorMetadata($processorMetadata);

        $saverResultData = new ProcessingResultData($dto, $options);

        if (!$processorMetadata->isTransacted()) {
            $processorMetadata
                ->setTransacted(true)
                ->setTransacted(true, true);
            $this->beginTransaction();
        }

        try {
            $entity = $this->saveSingleDto($dto, $saverResultData, $processorMetadata);

            $this->finalize([$entity], [$dto], $saverResultData, $processorMetadata);
        } catch (Throwable $ex) {
            return $this->processException($ex, $saverResultData, $processorMetadata);
        }

        return $this->processResult($entity, $saverResultData, $processorMetadata);
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
            if ($validator instanceof DtoValidatorInterface) {
                $this->validators[self::VALIDATOR_CATEGORY_DTO][spl_object_hash($validator)] = $validator;
            }
            if ($validator instanceof EntityValidatorInterface) {
                $this->validators[self::VALIDATOR_CATEGORY_ENTITY][spl_object_hash($validator)] = $validator;
            }
            if ($validator instanceof EntityIntegrityValidatorInterface) {
                $this->validators[self::VALIDATOR_CATEGORY_ENTITY_INTEGRITY][spl_object_hash($validator)] = $validator;
            }
            if ($validator instanceof DataIntegrityValidatorInterface) {
                $this->validators[self::VALIDATOR_CATEGORY_DATA_INTEGRITY][spl_object_hash($validator)] = $validator;
            }
        }
    }

    //endregion Public

//region SECTION: Getters/Setters

//endregion Getters/Setters
}
