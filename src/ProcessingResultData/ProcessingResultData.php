<?php


namespace Demoniqus\EntityProcessor\ProcessingResultData;

use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\Traits\ErrorsSubscriberTrait;
use Demoniqus\EntityProcessor\Traits\ProcessingResultDataTrait;

/**
 * Каждая отдельная операция сохранения saver'ом является по сути транзакцией,
 * в которой участвуют входящие данные, а также могут накапливаться некоторые
 * промежуточные данные. Данный класс отвечает за сохранение всех этих данных
 */
final class ProcessingResultData implements EntityProcessingResultDataInterface
{
    use ErrorsSubscriberTrait {
        ErrorsSubscriberTrait::addError as protected _baseAddError;
    }
    use ProcessingResultDataTrait;
    //region SECTION: Fields
    private $input = null;

    private $result = null;

    private array $processingTypes = [];

    private array $changeSet = [];

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
    //endregion Public

    //region SECTION: Getters/Setters
    public function getInput()
    {
        return $this->input;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getEntityChanges(EntityInterface $entity, string $entityClass): array
    {
        return $this->changeSet[$entityClass][$entity->getId()] ?? [];
    }
    public function getEntityChange(EntityInterface $entity, string $entityClass, string $entityField): array
    {
        return $this->changeSet[$entityClass][$entity->getId()][$entityField] ?? [null, null];
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
