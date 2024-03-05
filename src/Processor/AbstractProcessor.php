<?php


namespace Demoniqus\EntityProcessor\Processor;


use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Chain\EntityRemoverChainItem;
use Demoniqus\EntityProcessor\Chain\EntitySaverChainItem;
use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\DtoSavingFailedException;
use Demoniqus\EntityProcessor\Exception\DtoValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;
use Demoniqus\EntityProcessor\Exception\NextEntityProcessorException;
use Demoniqus\EntityProcessor\Interfaces\DtoCreatorFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityProcessorMetadataInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverChainItemInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\Preserve\EntityProcessingResultDataInterface as PreserveEntityProcessingResultDataInterface;
use Demoniqus\EntityProcessor\Metadata\EntityProcessorMetadata;
use Demoniqus\EntityProcessor\Remover\AbstractEntityRemover;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;
use Exception;
use Throwable;

abstract class AbstractProcessor
{
//region SECTION: Fields
    protected EntitySaverFactoryInterface $entitySaverFactory;
    protected EntityRemoverFactoryInterface $entityRemoverFactory;

    protected DtoCreatorFactoryInterface $dtoCreatorFactory;

    /**
     * @var string[]
     */
    protected array $serviceFields = [];

    protected array $nextProcessors = [];

    protected ?AbstractProcessor $prevProcessor = null;
//endregion Fields

//region SECTION: Constructor
    public function __construct(
        EntitySaverFactoryInterface $entitySaverFactory,
        EntityRemoverFactoryInterface $entityRemoverFactory,
        DtoCreatorFactoryInterface $dtoCreatorFactory
    )
    {
        $this->entitySaverFactory = $entitySaverFactory;
        $this->entityRemoverFactory = $entityRemoverFactory;
        $this->dtoCreatorFactory = $dtoCreatorFactory;
    }
//endregion Constructor

//region SECTION: Protected
    /**
     * @param DtoInterface|null                                                               $dto
     * @param EntityInterface                                                                 $entity
     * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @param EntityProcessorMetadataInterface                                                $processorMetadata
     * @throws DataIntegrityValidationException
     * @throws DtoSavingFailedException
     * @throws DtoValidationFailedException
     * @throws EntityIntegrityValidationFailedException
     * @throws EntityProcessorException
     * @throws FinalRecalculationFailedException
     * @throws NextEntityProcessorException
     * @throws Throwable
     */
    final protected function callNextProcessors(
        ?DtoInterface $dto,
        EntityInterface $entity,
        PreserveEntityProcessingResultDataInterface $errorSubscriber,
        EntityProcessorMetadataInterface $processorMetadata
    ): void
    {
        /**
         * Saver'ы могут образовывать цепочки нескольких типов:
         * - естественные - обусловлены логическими связями различных сущностей. Например, при создании BudgetItem необходимо
         *      создавать соответствующие ему DI и DD. Эту логику знает saver, а пользователь не обязан за нее отвечать.
         * - пользовательские - обусловлены разработчиком. Например, разработчик создает интерфейс для одновременного
         *      редактирования всех типов BudgetItem. Каждый тип Budget'ов может по своей природе редактироваться вполне
         *      независимо от других, однако пользователь хочет сразу в одном интерфейсе ввести набор изменений, которые
         *      вкупе являются допустимыми, хотя их последовательное применение могло бы выйти за рамки допустимого
         *      (например, изменения первого выполнения приведут к превышению сумм сметы, а изменения второго выполнения
         *      компенсируют это).
         * Во втором случае разработчик должен самостоятельно определить цепочки необходимых ему saver'ов. Однако
         * выбранные разработчиком saver'ы могут образовывать между собой цепочки естественного типа. При этом недопустимо,
         * чтобы вызванные через естественную цепочку saver'ы пытались обратиться друг к другу по пользовательским цепочкам.
         * Поэтому saver'ы реализованы как сервисы со множественной имплементацией. Разработчик может создать собственную
         * цепочку saver'ов. Однако, если какой-то из выбранных saver'ов прямо или опосредованно вызывает другой выбранный
         * saver из-за логических связей между обрабатываемыми сущностями, то выполнение не пойдет повторно по цепочке,
         * определенной разработчиком.
         */

        /** @var EntityProcessorChainItemInterface $item */
        foreach ($this->nextProcessors as $item) {
            if ($item instanceof EntitySaverChainItemInterface) {
                $nextProcessorResult = $item->getProcessor()->saveSet(
                    $item->getDtos($dto, $entity),
                    $errorSubscriber->getOptions(),
                    $processorMetadata
                );
            }
            else if ($item instanceof EntityRemoverChainItemInterface) {
                $nextProcessorResult = $item->getProcessor()->deleteSet(
                    $item->getEntities($entity),
                    $errorSubscriber->getOptions(),
                $processorMetadata
                );
            }
            else {
                /**
                 * Сюда никогда не попадем
                 */
                throw new Exception();
            }

            $this->processNextProcessorResult($item, $nextProcessorResult, $errorSubscriber);
        }
    }
    /**
     * Перед выполнением новой операции сохранения/удаления может потребоваться приведение processor'а к начальному состоянию
     */
    protected function init(): void
    {

    }

	/**
     * @param EntityProcessorChainItemInterface                                                   $item
     * @param EntityProcessingResultDataInterface                                             $nextProcessorResult
     * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $errorSubscriber
     * @throws NextEntityProcessorException
     */
    final protected function processNextProcessorResult(
        EntityProcessorChainItemInterface $item,
        EntityProcessingResultDataInterface $nextProcessorResult,
        PreserveEntityProcessingResultDataInterface $errorSubscriber
	): void
    {
        $errorSubscriber->inheritErrors($nextProcessorResult);
        if ($errorSubscriber->hasErrors()) {
            $className = get_class($item->getProcessor());
            $className = explode('\\', $className);
            $className = array_pop($className);
            throw new NextEntityProcessorException('При обработке связанных данных классом ' . $className . ' обнаружены ошибки.');
        }
    }

    /**
     * @param Throwable                                                                       $ex
     * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $processorResultData
     * @param EntityProcessorMetadataInterface                                                $processorMetadata
     * @return EntityProcessingResultDataInterface
     * @throws Throwable
     */
    protected function processException(
        Throwable                                   $ex,
        PreserveEntityProcessingResultDataInterface $processorResultData,
        EntityProcessorMetadataInterface            $processorMetadata
    ): EntityProcessingResultDataInterface
    {
        if ($processorMetadata->isTransacted(true)) {
            $this->rollback();
        }
        /**
         * Известные ошибки складываем в конечный результат
         */
        if ($ex instanceof EntityProcessorException) {
            if ($ex->getMessage()) {
                $processorResultData->addError($ex->getMessage());
            }
			else {
				$processorResultData->addError(get_class($ex));
			}

            return $processorResultData->finalize();
        }

        /**
         * Непонятную ошибку просто выстреливаем дальше
         */
        throw $ex;
    }

    /**
     * @param                                                                                 $result
     * @param PreserveEntityProcessingResultDataInterface|EntityProcessingResultDataInterface $processingResultData
     * @param EntityProcessorMetadataInterface                                                $processorMetadata
     * @return EntityProcessingResultDataInterface
     */
    final protected function processResult(
        $result,
        PreserveEntityProcessingResultDataInterface $processingResultData,
        EntityProcessorMetadataInterface $processorMetadata
    ): EntityProcessingResultDataInterface
    {
		if (
			!$processingResultData->hasErrors() &&
			(
				$processingResultData->getOption(EntityProcessorInterface::AVOID_INTERMEDIATE_FIXING, $this) !== true ||
				$processingResultData->getOption(EntityProcessorInterface::AVOID_INTERMEDIATE_FIXING, static::class) !== true ||
				$processingResultData->getOption(EntityProcessorInterface::AVOID_INTERMEDIATE_FIXING) !== true ||
				$processorMetadata->isTransacted(true)
			)
		) {
			$this->flush();
		}
        if ($processorMetadata->isTransacted(true)) {
            if (!$processingResultData->hasErrors()) {
                $this->commit();
            } else {
                $this->rollback();
            }
        }

        $processingResultData->setResult($result);

        return $processingResultData->finalize();
    }

    /**
     * @param AbstractProcessor $processor
     * @param callable          $getProcessedItems
     * @param                   $context
     * @throws Exception
     */
    final protected function createNextChainItem(AbstractProcessor $processor, callable $getProcessedItems, $context = null): void
    {
        if ($processor instanceof AbstractEntityRemover) {
            $this->nextProcessors[] = new EntityRemoverChainItem($processor, $context, $getProcessedItems);
            return;
        }
        if ($processor instanceof AbstractEntitySaver) {
            $this->nextProcessors[] = new EntitySaverChainItem($processor, $context, $getProcessedItems);
            return;
        }
        throw new Exception();
    }

    protected function getProcessorMetadata(?EntityProcessorMetadataInterface $processorMetadata): EntityProcessorMetadataInterface
    {
        return $processorMetadata ?
            $processorMetadata->createNewLayer($this) :
            new EntityProcessorMetadata($this);
    }

    abstract protected function getEntityClass(): string;

	/**
	 * Начало транзакции
	 * @return void
	 */
	abstract protected function beginTransaction(): void;

	/**
	 * Фиксация транзакции. Если хранилище не обладает возможностью использования транзакций,
	 * вероятно, метод flush сразу выполняет фиксацию изменений прямо в хранилище. В таком случае
	 * данный метод следует оставить пустым.
	 * @return void
	 */
	abstract protected function commit(): void;

	/**
	 * Откат транзакции. Если хранилище не обладает возможностью использования транзакций,
	 * нужно либо оставить данный метод пустым, либо предусмотреть иной способ отката всех
	 * совершенных в рамках одной операции изменений.
	 * @return void
	 */
	abstract protected function rollback(): void;

	/**
	 * Фиксация изменений обрабатываемой сущности. Изменения могут фиксироваться как в
	 * транзакцию, так и непосредственно в хранилище. Также изменения могут фиксироваться
	 * как по отдельной сущности, так и сразу по целому набору данных - зависит от конкретной
	 * реализации.
	 *
	 * @return void
	 */
	abstract protected function flush(): void;

	abstract protected function findEntity(?int $id): ?EntityInterface;
//endregion Protected

//region SECTION: Public
    /**
     * Добавление полей сущностей, изменения которых не должны отслеживаться.
     *
     * @param array $fields
     * @noinspection PhpUnused
     */
    public function addServiceFields(array $fields): void
    {
        $this->serviceFields = array_combine($fields, $fields);
    }

    /**
     * @param EntityProcessorInterface $processor
     * @param callable          $getProcessedItems
     * @param null              $context
     * @throws Exception
	 * @noinspection PhpUnused
	 */
    public function setNext(EntityProcessorInterface $processor, callable $getProcessedItems, $context = null): void
    {
        if ($processor->prevProcessor) {
            $processor->prevProcessor->removeNext($processor);
        }

        $processor->prevProcessor = $this;

        $this->createNextChainItem($processor, $getProcessedItems, $context);
    }

    abstract public function addValidators(iterable $validators): void;
//endregion Public
//region SECTION: Getters/Setters

    public function removeNext(EntityProcessorInterface $processor): void
    {
        $processors = [];

        foreach ($this->nextProcessors as $nextProcessorChainItem) {
            if (!$nextProcessorChainItem->isProcessor($processor)) {
                $processors[] = $nextProcessorChainItem;
            } else {
                $processor->prevProcessor = null;
            }
        }

        $this->nextProcessors = $processors;
    }
//endregion Getters/Setters
}