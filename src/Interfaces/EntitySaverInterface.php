<?php


namespace Demoniqus\EntityProcessor\Interfaces;


use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\DtoSavingFailedException;
use Demoniqus\EntityProcessor\Exception\DtoValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;

interface EntitySaverInterface extends UnsharedServiceInterface, EntityProcessorInterface
{
//region SECTION: Fields
    /**
     * В некоторых случаях проверки dto и entity (перед сохранением) идентичны, а потому в целях экономии
     * времени можно их не выполнять сразу обе, а оставить лишь одну
     */
    const SKIP_DTO_VALIDATION = 'skipDtoValidation';
    const SKIP_ENTITY_VALIDATION = 'skipEntityValidation';
//endregion Fields
//region SECTION: Public
//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param DtoInterface                          $dto
     * @param ProcessorOptionsInterface|null        $options
     * @param EntityProcessorMetadataInterface|null $processorMetadata
     * @return EntityProcessingResultDataInterface
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityProcessorException
     * @throws EntityIntegrityValidationFailedException
     * @throws FinalRecalculationFailedException
     * @throws \Throwable
     */
    public function save(
        DtoInterface $dto,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface;

    /**
     * @param DtoInterface[]                 $dtos
     * @param ProcessorOptionsInterface|null $options
     * @param EntityProcessorMetadataInterface|null $processorMetadata
     * @return EntityProcessingResultDataInterface
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityProcessorException
     * @throws EntityIntegrityValidationFailedException
     * @throws FinalRecalculationFailedException
     * @throws \Throwable
     */
    public function saveSet(
        array $dtos,
        ?ProcessorOptionsInterface $options = null,
        ?EntityProcessorMetadataInterface $processorMetadata = null
    ): EntityProcessingResultDataInterface;
//endregion Getters/Setters
}