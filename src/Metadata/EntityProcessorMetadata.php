<?php


namespace Demoniqus\EntityProcessor\Metadata;


use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;

final class EntityProcessorMetadata implements EntityProcessorMetadataInterface
{
//region SECTION: Fields
    private $caller;
    private ?EntityProcessorMetadata $parent = null;
    /**
     * Весь процесс обернут в транзакцию
     * @var bool
     */
    private bool $isTransacted = false;
    /**
     * Текущий слой процесса завернут в транзакцию
     * @var bool
     */
    private bool $isTransactedLayer = false;
//endregion Fields

//region SECTION: Constructor
    public function __construct(
        $caller
    )
    {
        $this->caller = $caller;
    }
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public
    public function createNewLayer($caller): EntityProcessorMetadataInterface
    {
        $newLayer = new self($caller);
        $newLayer->parent = $this;
        $newLayer->isTransacted = &$this->isTransacted;

        return $newLayer;
    }
//endregion Public

//region SECTION: Getters/Setters
    public function setTransacted(bool $isTransacted, bool $thisLayer = false): EntityProcessorMetadataInterface
    {
        if ($thisLayer) {
            $this->isTransactedLayer = $isTransacted;
        }
        else {
            $this->isTransacted = $isTransacted;
        }

        return $this;
    }

    public function isTransacted(bool $thisLayer = false): bool
    {
        return $thisLayer ? $this->isTransactedLayer : $this->isTransacted;
    }

    public function isParentCaller(string $className): bool
    {
        $parentClassName = $this->parent ?
            get_class($this->parent->caller) :
            null;

        return $parentClassName === $className;
    }
//endregion Getters/Setters
}