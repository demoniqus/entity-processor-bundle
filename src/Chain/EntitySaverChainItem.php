<?php


namespace Demoniqus\EntityProcessor\Chain;


use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverInterface;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;

final class EntitySaverChainItem extends AbstractEntityProcessorChainItem implements EntitySaverChainItemInterface
{
//region SECTION: Fields
    /**
     * @var \Closure|callable(?DtoInterface, ?EntityInterface):DtoInterface[]
     */
    private \Closure $getDtosCallback;
//endregion Fields

//region SECTION: Constructor
    /**
     * @param AbstractEntitySaver                                    $processor
     * @param                                                        $context
     * @param callable(?DtoInterface, ?EntityInterface):DtoInterface[] $getDtos
     */
    public function __construct(AbstractProcessor $processor, $context, callable $getDtos)
    {
        parent::__construct($processor, $context);
        $this->getDtosCallback = $getDtos;
    }
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
    public function getDtos(?DtoInterface $dto, ?EntityInterface $entity): array
    {
        return $this->getDtosCallback->call($this->context ?? new class {}, $dto, $entity);
    }

	/** @noinspection PhpRedundantMethodOverrideInspection */
	public function getProcessor(): EntitySaverInterface
    {
        return parent::getProcessor();
    }
//endregion Getters/Setters
}