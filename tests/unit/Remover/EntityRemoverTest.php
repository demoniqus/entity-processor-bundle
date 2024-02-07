<?php

namespace Demoniqus\EntityProcessor\Tests\unit\Remover;

use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\EntityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;
use Demoniqus\EntityProcessor\Factory\DtoCreatorFactory;
use Demoniqus\EntityProcessor\Factory\EntityRemoverFactory;
use Demoniqus\EntityProcessor\Factory\EntitySaverFactory;
use Demoniqus\EntityProcessor\Interfaces\DataIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\ErrorSubscriberInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\Interfaces\ValidatorInterface;
use Demoniqus\EntityProcessor\Metadata\EntityProcessorMetadata;
use Demoniqus\EntityProcessor\ProcessingResultData\Preserve\ProcessingResultData as PreserveProcessingResultData;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\ProcessorOptions\ProcessorOptions;
use Demoniqus\EntityProcessor\Remover\AbstractEntityRemover;
use Demoniqus\EntityProcessor\Tests\Dummy\Exception\SpecificTestException;
use Demoniqus\EntityProcessor\Tests\WebTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EntityRemoverTest extends WebTestCase
{
//region SECTION: Fields
	public const ENTITY_VALIDATION_ERROR = 'EntityValidationError';
	public const ENTITY_INTEGRITY_VALIDATION_ERROR = 'EntityIntegrityValidationError';
	public const DATA_INTEGRITY_VALIDATION_ERROR = 'DataIntegrityValidationError';

	/**
	 * @var MockObject|AbstractProcessor|(AbstractProcessor&MockObject)|null
	 */
	private ?MockObject $tested = null;
//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 
	protected function setUp()
	{
		$client = static::createClient();
		$container = $client->getContainer();
		$this->tested = $this->getMockForAbstractClass(
			AbstractEntityRemover::class,
			[
				$container->get(EntitySaverFactory::class),
				$container->get(EntityRemoverFactory::class),
				$container->get(DtoCreatorFactory::class)
			]

		);
	}
//endregion Protected

//region SECTION: Private
	private function getEntity(): EntityInterface
	{
		return new class () implements EntityInterface {

			function getId(): ?int
			{
				return 100;
			}
		};
	}


	private function getProcessingDataResult(): PreserveProcessingResultData
	{
		return new PreserveProcessingResultData(null, new ProcessorOptions());
	}

	private function getFailedValidator(): ValidatorInterface
	{
		return  new class() implements EntityValidatorInterface, EntityIntegrityValidatorInterface,
			DataIntegrityValidatorInterface
		{
			/**
			 * @var ErrorSubscriberInterface[]
			 */
			private array $subscribers = [];

			protected function addError($message, $identifier  = null, $key = null) {
				foreach ($this->subscribers as $subscriber) {
					$subscriber->addError($message, $identifier, $key);
				}
			}

			public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				$this->subscribers[spl_object_hash($errorSubscriber)] = $errorSubscriber;
			}

			public function rejectErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				unset($this->subscribers[spl_object_hash($errorSubscriber)]);
			}

			public function validateEntity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::ENTITY_VALIDATION_ERROR);
			}

			public function validateEntityIntegrity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::ENTITY_INTEGRITY_VALIDATION_ERROR);
			}

			public function validateData(array $entitiesSet, ?array $dtosSet = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::DATA_INTEGRITY_VALIDATION_ERROR);
			}

			public function getSubscribers(): array
			{
				return $this->subscribers;
			}
		};
	}
	private function getFailedEntityIntegrityValidator(): EntityIntegrityValidatorInterface
	{
		return  new class() implements EntityIntegrityValidatorInterface
		{
			/**
			 * @var ErrorSubscriberInterface[]
			 */
			private array $subscribers = [];

			protected function addError($message, $identifier  = null, $key = null) {
				foreach ($this->subscribers as $subscriber) {
					$subscriber->addError($message, $identifier, $key);
				}
			}

			public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				$this->subscribers[spl_object_hash($errorSubscriber)] = $errorSubscriber;
			}

			public function rejectErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				unset($this->subscribers[spl_object_hash($errorSubscriber)]);
			}

			public function validateEntityIntegrity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::ENTITY_INTEGRITY_VALIDATION_ERROR);
			}
		};
	}
	private function getFailedEntityValidator(): EntityValidatorInterface
	{
		return  new class() implements EntityValidatorInterface
		{
			/**
			 * @var ErrorSubscriberInterface[]
			 */
			private array $subscribers = [];

			protected function addError($message, $identifier  = null, $key = null) {
				foreach ($this->subscribers as $subscriber) {
					$subscriber->addError($message, $identifier, $key);
				}
			}

			public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				$this->subscribers[spl_object_hash($errorSubscriber)] = $errorSubscriber;
			}

			public function rejectErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				unset($this->subscribers[spl_object_hash($errorSubscriber)]);
			}

			public function validateEntity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::ENTITY_VALIDATION_ERROR);
			}
		};
	}
	private function getFailedDataIntegrityValidator(): DataIntegrityValidatorInterface
	{
		return  new class() implements DataIntegrityValidatorInterface
		{
			/**
			 * @var ErrorSubscriberInterface[]
			 */
			private array $subscribers = [];

			protected function addError($message, $identifier  = null, $key = null) {
				foreach ($this->subscribers as $subscriber) {
					$subscriber->addError($message, $identifier, $key);
				}
			}

			public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				$this->subscribers[spl_object_hash($errorSubscriber)] = $errorSubscriber;
			}

			public function rejectErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				unset($this->subscribers[spl_object_hash($errorSubscriber)]);
			}

			public function validateData(array $entitiesSet, ?array $dtosSet = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntityRemoverTest::DATA_INTEGRITY_VALIDATION_ERROR);
			}
		};
	}
//endregion Private

//region SECTION: Public
	public function testValidateBeforeDelete()
	{
		$entity = $this->getEntity();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($entity, $processingDataResult, $tester) {
				$result = $this->validateBeforeDelete($entity, $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор beforeDelete не сработал или не смог передать ошибки');
				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntityRemoverTest::ENTITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка');

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testValidateEntityIntegrity()
	{
		$entity = $this->getEntity();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($entity, $processingDataResult, $tester) {
				$result = $this->validateAfterDelete($entity, $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор afterDelete не сработал или не смог передать ошибки');

				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntityRemoverTest::ENTITY_INTEGRITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка') ;

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();

	}
	public function testValidateDataIntegrity()
	{
		$entity = $this->getEntity();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($entity, $processingDataResult, $tester) {
				$result = $this->validateDataIntegrity([$entity], $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор dataIntegrity не сработал или не смог передать ошибки');

				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntityRemoverTest::DATA_INTEGRITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка') ;

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();

	}
	public function testCrashOnBeforeDeleteValidationFailed()
	{
		$entity = $this->getEntity();

		$this->tested->addValidators([$this->getFailedEntityValidator()]);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(EntityValidationFailedException::class);
		\Closure::bind(
			function() use ($entity, $processingDataResult) {
				$this->deleteSingleEntity($entity, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnAfterDeleteValidationFailed()
	{
		$entity = $this->getEntity();

		$this->tested->addValidators([$this->getFailedEntityIntegrityValidator()]);
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(EntityIntegrityValidationFailedException::class);
		\Closure::bind(
			function() use ($entity, $processingDataResult) {
				$this->deleteSingleEntity($entity, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnDataIntegrityValidationFailed()
	{
		$entity = $this->getEntity();

		$this->tested->addValidators([$this->getFailedDataIntegrityValidator()]);
		$this->tested->method('recalculateAfterDeleting')->willReturn(true);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(DataIntegrityValidationException::class);
		\Closure::bind(
			function() use ($entity, $processingDataResult) {
				$this->finalize([$entity], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnFinalRecalculationFailed()
	{
		$entity = $this->getEntity();

		$this->tested->method('recalculateAfterDeleting')->willReturn(false);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(FinalRecalculationFailedException::class);
		\Closure::bind(
			function() use ($entity, $processingDataResult) {
				$this->finalize([$entity], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnFinalizeFailed()
	{
		$entity = $this->getEntity();

		$processingDataResult = $this->getProcessingDataResult();
		$processingDataResult->addError('Test error');

		$this->expectException(EntityProcessorException::class);
		\Closure::bind(
			function() use ($entity, $processingDataResult) {
				$this->finalize([$entity], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testSuccessDeleteSingleDto()
	{
		$entity = $this->getEntity();
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$processingDataResult = $this->getProcessingDataResult();

		$tester = $this;

		\Closure::bind(
			function() use ($entity, $processingDataResult, $tester) {
				$result = $this->deleteSingleEntity($entity, $processingDataResult, new EntityProcessorMetadata($this));

				$tester::assertTrue($result);

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testValidatorsSorting()
	{
		$this->tested->addValidators([$this->getFailedValidator()]);

		$tester = $this;

		\Closure::bind(
			function() use ($tester) {
				foreach ($this->validators as $category => $validators) {
					$expectedInterface = null;
					switch ($category) {
						case EntityRemoverInterface::VALIDATOR_CATEGORY_BEFORE_DELETE:
							$expectedInterface = EntityValidatorInterface::class;
							break;
						case EntityRemoverInterface::VALIDATOR_CATEGORY_AFTER_DELETE:
							$expectedInterface = EntityIntegrityValidatorInterface::class;
							break;
						case EntityRemoverInterface::VALIDATOR_CATEGORY_DATA_INTEGRITY:
							$expectedInterface = DataIntegrityValidatorInterface::class;
							break;
						default:
							$tester::fail('Неизвестный тип валидаторов. Невозможно выполнить тестирование');

					}
					foreach ($validators as $validator) {
						$tester::assertInstanceOf($expectedInterface, $validator, 'Нарушена сортировка валидаторов');
					}
				}

			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testCrashOnSaveSingleDtoThrowOtherException_singleEntity()
	{
		$entity = $this->getEntity();
		$this->tested->method('deleteEntity')->willThrowException(new SpecificTestException());

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($entity) {
				$this->delete($entity);
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowOtherException_singleEntity()
	{
		$entity = $this->getEntity();
		$this->tested->method('recalculateAfterDeleting')->willThrowException(new SpecificTestException());
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($entity) {
				$this->delete($entity);
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnSaveSingleDtoThrowOtherException_entitiesSet()
	{
		$entity = $this->getEntity();
		$this->tested->method('deleteEntity')->willThrowException(new SpecificTestException());

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($entity) {
				$this->deleteSet([$entity]);
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowOtherException_entitiesSet()
	{
		$entity = $this->getEntity();
		$this->tested->method('recalculateAfterDeleting')->willThrowException(new SpecificTestException());
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($entity) {
				$this->deleteSet([$entity]);
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testCrashOnSaveSingleDtoThrowEntityProcessorException_singleEntity()
	{
		$entity = $this->getEntity();
		$this->tested->method('deleteEntity')->willThrowException(new EntityValidationFailedException());

		$tester = $this;

		\Closure::bind(
			function() use ($entity, $tester) {
				$result = $this->delete($entity);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(EntityValidationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnSaveSingleDtoThrowEntityProcessorException_entitiesSet()
	{
		$entity = $this->getEntity();
		$this->tested->method('deleteEntity')->willThrowException(new EntityValidationFailedException());

		$tester = $this;

		\Closure::bind(
			function() use ($entity, $tester) {
				$result = $this->deleteSet([$entity]);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(EntityValidationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowEntityProcessorException_singleEntity()
	{
		$entity = $this->getEntity();
		$this->tested->method('recalculateAfterDeleting')->willThrowException(new FinalRecalculationFailedException());
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$tester = $this;

		\Closure::bind(
			function() use ($entity, $tester) {
				$result = $this->delete($entity);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(FinalRecalculationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
	public function testCrashOnFinalizeThrowEntityProcessorException_entitiesSet()
	{
		$entity = $this->getEntity();
		$this->tested->method('recalculateAfterDeleting')->willThrowException(new FinalRecalculationFailedException());
		$this->tested->method('deleteEntity')->willReturn(true);
		$this->tested->method('getEntityClass')->willReturn(get_class($entity));

		$tester = $this;

		\Closure::bind(
			function() use ($entity, $tester) {
				$result = $this->deleteSet([$entity]);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(FinalRecalculationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntityRemover::class
		)->__invoke();
	}
//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
}