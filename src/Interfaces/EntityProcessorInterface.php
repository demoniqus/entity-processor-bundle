<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityProcessorInterface
{
//region SECTION: Fields
	/**
	 * В некоторых случаях можно не выполнять промежуточные фиксации сделанных processor'ами изменений, а выполнить лишь
	 * одно сохранение в конце всей операции изменения. Однако надо быть с этим очень осторожным, поскольку
	 * любой processor в цепочке может обращаться в хранилище за теми или иными данными, полагая, что там все данные актуальны.
	 */
	const AVOID_INTERMEDIATE_FIXING = 'avoidIntermediateFixing';
//endregion Fields
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param EntityProcessorInterface $processor
     * @param callable            $getProcessedItems
     * @param                     $context
     */
    function setNext(EntityProcessorInterface $processor, callable $getProcessedItems, $context = null): void;

    /**
     * @param EntityProcessorInterface $processor
     */
    function removeNext(EntityProcessorInterface $processor): void;
//endregion Getters/Setters
}