<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface EntityRemoverInterface extends EntityProcessorInterface
{
//region SECTION: Fields
    const VALIDATOR_CATEGORY_BEFORE_DELETE = 'beforeDelete';
    const VALIDATOR_CATEGORY_AFTER_DELETE = 'afterDelete';
    const VALIDATOR_CATEGORY_DATA_INTEGRITY = 'dataIntegrity';
//endregion Fields
//region SECTION:Public
    function delete(
        EntityInterface $entity,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface;

    function deleteSet(
        array $entities,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface;
//endregion Public
//region SECTION: Getters/Setters

//endregion Getters/Setters
}