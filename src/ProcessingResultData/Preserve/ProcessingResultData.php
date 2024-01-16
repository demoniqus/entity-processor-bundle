<?php


namespace Demoniqus\EntityProcessor\ProcessingResultData\Preserve;

use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntitySaverResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\ProcessingResultData\ProcessingResultData as ReadonlyProcessingResultData;
use Demoniqus\EntityProcessor\Traits\ErrorsSubscriberTrait;
use Demoniqus\EntityProcessor\Traits\ProcessingResultDataTrait;

/**
 * Каждая отдельная операция сохранения saver'ом является по сути транзакцией,
 * в которой участвуют входящие данные, а также могут накапливаться некоторые
 * промежуточные данные. Данный класс отвечает за сохранение всех этих данных
 */
final class ProcessingResultData implements EntityProcessingResultDataInterface, PreserveEntitySaverResultDataInterface
{
    use ErrorsSubscriberTrait, ProcessingResultDataTrait;
    //region SECTION: Fields
    private $input = null;

    private $result = null;

    private array $changeSet       = [];
    private array $processingTypes = [];

    private ?ProcessorOptionsInterface $options = null;

    //endregion Fields

    //region SECTION: Constructor
    public function __construct($input = null, ?ProcessorOptionsInterface $options = null)
    {
        $this->input = $input;
        $this->options = $options;
    }
    //endregion Constructor

    //region SECTION: Protected

    //endregion Protected

    //region SECTION: Private

    //endregion Private

    //region SECTION: Public

    public function finalize(): EntityProcessingResultDataInterface
    {
        $roResult = new ReadonlyProcessingResultData($this->input, $this->options);

        $errors = $this->errors;
        $result = $this->result;
        $changeSet = $this->changeSet;
        $processingTypes = $this->processingTypes;

        \Closure::bind(
            function() use ($errors, $result, $changeSet, $processingTypes) {
                $this->errors = $errors;
                $this->result = $result;
                $this->changeSet = $changeSet;
                $this->processingTypes = $processingTypes;
            },
            $roResult,
            ReadonlyProcessingResultData::class
        )->__invoke();

        return $roResult;
    }
    //endregion Public

    //region SECTION: Getters/Setters

    /**
     * @param $result
     * @throws \Exception
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     */
    public function setEntityAsCreated(EntityInterface $entity, string $entityClass): void
    {
        $this->processingTypes[$entityClass][$entity->getId()] = 'created';
    }
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     */
    public function setEntityAsUpdated(EntityInterface $entity, string $entityClass): void
    {
        $this->processingTypes[$entityClass][$entity->getId()] = 'updated';
    }
    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     */
    public function setEntityAsDeleted(EntityInterface $entity, string $entityClass): void
    {
        $this->processingTypes[$entityClass][$entity->getId()] = 'deleted';
    }

    /**
     * @param EntityInterface $entity
     * @param string          $entityClass
     * @param array           $changeSet
     * @throws \Exception
     */
    public function addChangeSet(EntityInterface $entity, string $entityClass,  array $changeSet): void
    {
        $this->changeSet[$entityClass][$entity->getId()] = $changeSet;
    }

    public function addEntityChanging(EntityInterface $entity, string $entityClass, array $changeSet): void
    {
        foreach ($changeSet as $fieldName => $changes) {
            $this->changeSet[$entityClass][$entity->getId()][$fieldName] = $changes;
        }
    }

    public function getEntityChanges(EntityInterface $entity, string $entityClass): array
    {
        return $this->changeSet[$entityClass][$entity->getId()] ?? [];
    }

    public function getEntityChange(EntityInterface $entity, string $entityClass, string $entityField): array
    {
        return $this->changeSet[$entityClass][$entity->getId()][$entityField] ?? [null, null];
    }

    /**
     * @param EntityProcessingResultDataInterface $source
     * @throws \Exception
     */
    public function inheritErrors(EntityProcessingResultDataInterface $source): void
    {
        foreach ($source->getErrors() as $error) {
            $this->addError($error);
        }
    }
    public function getOption(string $optionName, $object = null)
    {
        return $this->options ? $this->options->getOption($optionName, $object) : null;
    }

    public function getOptions(): ?ProcessorOptionsInterface
    {
        return $this->options;
    }
    //endregion Getters/Setters
}
