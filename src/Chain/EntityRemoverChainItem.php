<?php


namespace Demoniqus\EntityProcessor\Chain;


use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverInterface;
use Demoniqus\EntityProcessor\Remover\AbstractEntityRemover;

final class EntityRemoverChainItem extends AbstractEntityProcessorChainItem implements EntityRemoverChainItemInterface
{
//region SECTION: Fields
    /**
     * @var \Closure|callable(EntityInterface):EntityInterface[]
     */
    private \Closure $getEntitiesCallback;
//endregion Fields

//region SECTION: Constructor
    /**
     * @param AbstractEntityRemover                       $processor
     * @param                                             $context
     * @param callable(EntityInterface):EntityInterface[] $getEntities
     */
    public function __construct(EntityProcessorInterface $processor, $context, callable $getEntities)
    {
        /**
         * Проверка типов $processor идет на уровне saver'ов и remover'ов, поэтому здесь нет смысла
         */
        parent::__construct($processor, $context);
        $this->getEntitiesCallback = $getEntities;
    }
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters

    public function getEntities(EntityInterface $entity): array
    {
        return $this->getEntitiesCallback->call($this->context ?? new class {}, $entity);
    }

	/** @noinspection PhpRedundantMethodOverrideInspection */
	public function getProcessor(): EntityRemoverInterface
    {
        return parent::getProcessor();
    }
//endregion Getters/Setters
}