<?php


namespace Demoniqus\EntityProcessor\Chain;


use Demoniqus\EntityProcessor\Interfaces\EntityProcessorChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorInterface;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;

abstract class AbstractEntityProcessorChainItem implements EntityProcessorChainItemInterface
{
//region SECTION: Fields
    private AbstractProcessor $processor;
    /**
     * context для getDtos или getEntities-функций
     */
    protected $context = null;
//endregion Fields

//region SECTION: Constructor
    /**
     * @param AbstractEntitySaver $processor
     * @param                     $context
     */
    public function __construct(AbstractProcessor $processor, $context)
    {
        $this->processor = $processor;
        $this->context = $context;
    }
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public
    public function isProcessor(AbstractProcessor $processor): bool
    {
        return $this->processor === $processor;
    }

	public function getProcessor(): EntityProcessorInterface
	{
		return $this->processor;
	}
//endregion Public

//region SECTION: Getters/Setters

//endregion Getters/Setters
}