<?php

namespace Demoniqus\EntityProcessor\Tests\unit\Saver;

use Demoniqus\EntityProcessor\Exception\DataIntegrityValidationException;
use Demoniqus\EntityProcessor\Exception\DtoSavingFailedException;
use Demoniqus\EntityProcessor\Exception\DtoValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityIntegrityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\EntityProcessorException;
use Demoniqus\EntityProcessor\Exception\EntityValidationFailedException;
use Demoniqus\EntityProcessor\Exception\FinalRecalculationFailedException;
use Demoniqus\EntityProcessor\Factory\DtoCreatorFactory;
use Demoniqus\EntityProcessor\Factory\EntityRemoverFactory;
use Demoniqus\EntityProcessor\Factory\EntitySaverFactory;
use Demoniqus\EntityProcessor\Interfaces\DataIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityIntegrityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityValidatorInterface;
use Demoniqus\EntityProcessor\Interfaces\ErrorSubscriberInterface;
use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;
use Demoniqus\EntityProcessor\Interfaces\ValidatorInterface;
use Demoniqus\EntityProcessor\Metadata\EntityProcessorMetadata;
use Demoniqus\EntityProcessor\ProcessingResultData\Preserve\ProcessingResultData as PreserveProcessingResultData;
use Demoniqus\EntityProcessor\Processor\AbstractProcessor;
use Demoniqus\EntityProcessor\ProcessorOptions\ProcessorOptions;
use Demoniqus\EntityProcessor\Saver\AbstractEntitySaver;
use Demoniqus\EntityProcessor\Tests\Dummy\Exception\SpecificTestException;
use Demoniqus\EntityProcessor\Tests\WebTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EntitySaverTest extends WebTestCase
{
//region SECTION: Fields
	public const DTO_VALIDATION_ERROR = 'DtoValidationError';
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
			AbstractEntitySaver::class,
			[
				$container->get(EntitySaverFactory::class),
				$container->get(EntityRemoverFactory::class),
				$container->get(DtoCreatorFactory::class)
			]

		);
	}
//endregion Protected

//region SECTION: Private
	private function getDto(): DtoInterface
	{
		$entity = new class () implements EntityInterface {

			function getId(): ?int
			{
				return 100;
			}
		};
		$dtoInterface = new class ($entity) implements DtoInterface {
			private ?int $id = null;
			private ?EntityInterface $entity = null;


			function getEntity()
			{
				return $this->entity;
			}

			function getId()
			{
				return $this->id;
			}

			function setEntity($entity)
			{
				$this->entity = $entity;

				return $this;
			}

			function setId($id)
			{
				$this->id = $id;

				return $this;
			}
		};

		$dtoInterface
			->setEntity($entity)
			->setId($entity->getId())
		;

		return $dtoInterface;
	}

	private function getProcessingDataResult(): PreserveProcessingResultData
	{
		return new PreserveProcessingResultData(null, new ProcessorOptions());
	}

	private function getFailedValidator(): ValidatorInterface
	{
		return  new class() implements DtoValidatorInterface,
			EntityValidatorInterface, EntityIntegrityValidatorInterface,
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

			public function validateDto(DtoInterface $dto, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntitySaverTest::DTO_VALIDATION_ERROR);
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
				$this->addError(EntitySaverTest::ENTITY_VALIDATION_ERROR);
			}

			public function validateEntityIntegrity(EntityInterface $entity, ?DtoInterface $dto = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntitySaverTest::ENTITY_INTEGRITY_VALIDATION_ERROR);
			}

			public function validateData(array $entitiesSet, ?array $dtosSet = null, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntitySaverTest::DATA_INTEGRITY_VALIDATION_ERROR);
			}

			public function getSubscribers(): array
			{
				return $this->subscribers;
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
				$this->addError(EntitySaverTest::DATA_INTEGRITY_VALIDATION_ERROR);
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
				$this->addError(EntitySaverTest::ENTITY_INTEGRITY_VALIDATION_ERROR);
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
				$this->addError(EntitySaverTest::ENTITY_VALIDATION_ERROR);
			}
		};
	}
	private function getFailedDtoValidator(): DtoValidatorInterface
	{
		return  new class() implements DtoValidatorInterface
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

			public function validateDto(DtoInterface $dto, ?ProcessorOptionsInterface $processorOptions = null): void
			{
				$this->addError(EntitySaverTest::DTO_VALIDATION_ERROR);
			}

			public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				$this->subscribers[spl_object_hash($errorSubscriber)] = $errorSubscriber;
			}

			public function rejectErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void
			{
				unset($this->subscribers[spl_object_hash($errorSubscriber)]);
			}
		};
	}
//endregion Private

//region SECTION: Public
	public function testValidateDto()
	{
		$dto = $this->getDto();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		$processingDataResult->getOptions()->setOption(
			EntitySaverInterface::SKIP_DTO_VALIDATION,
			true,
			$this->tested
		);

		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateDto($dto, $processingDataResult);
				$tester::assertTrue($result, 'При принудительной отмене валидации метод должен возвращать true');
				$tester::assertFalse($processingDataResult->hasErrors(), 'Не сработала команда принудительной отмены валидации DTO');
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateDto($dto, $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор DTO не сработал или не смог передать ошибки');
				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntitySaverTest::DTO_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка');

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

	}
	public function testValidateEntity()
	{
		$dto = $this->getDto();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		$processingDataResult->getOptions()->setOption(
			EntitySaverInterface::SKIP_ENTITY_VALIDATION,
			true,
			$this->tested
		);

		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateEntity($dto->getEntity(), $dto, $processingDataResult);
				$tester::assertTrue($result, 'При принудительной отмене валидации метод должен возвращать true');
				$tester::assertFalse($processingDataResult->hasErrors(), 'Не сработала команда принудительной отмены валидации entity');

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateEntity($dto->getEntity(), $dto, $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор entity не сработал или не смог передать ошибки');

				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntitySaverTest::ENTITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка') ;

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

	}
	public function testValidateEntityIntegrity()
	{
		$dto = $this->getDto();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateEntityIntegrity($dto->getEntity(), $dto, $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор entityIntegrity не сработал или не смог передать ошибки');

				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntitySaverTest::ENTITY_INTEGRITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка') ;

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

	}
	public function testValidateDataIntegrity()
	{
		$dto = $this->getDto();
		$tester = $this;

		$this->tested->addValidators([$this->getFailedValidator()]);

		$processingDataResult = $this->getProcessingDataResult();
		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$result = $this->validateDataIntegrity([$dto->getEntity()], [$dto], $processingDataResult);
				$tester::assertFalse($result, 'При обнаружении ошибок метод должен возвращать false');
				$tester::assertTrue($processingDataResult->hasErrors(), 'Валидатор dataIntegrity не сработал или не смог передать ошибки');

				$errors = $processingDataResult->getErrors();
				$tester::assertCount(1, $errors, 'Недопустимое количество обнаруженных ошибок');
				$tester::assertEquals(EntitySaverTest::DATA_INTEGRITY_VALIDATION_ERROR, $errors[0] ?? '', 'Не обнаружена ожидаемая ошибка') ;

				foreach ($this->validators as $type => $validators) {
					foreach ($validators as $validator) {
						$tester::assertCount(0, $validator->getSubscribers(), 'После выполнения валидации слушатель не отписался от рассылки и при повторном вызове валидатора может получить чужие ошибки');
					}
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();

	}
	public function testCrashOnDtoValidationFailed()
	{
		$dto = $this->getDto();

		$this->tested->addValidators([$this->getFailedDtoValidator()]);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(DtoValidationFailedException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->saveSingleDto($dto, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnDtoSavingFailed()
	{
		$dto = $this->getDto();
		$dto->setEntity(null)->setId(null);

		$this->tested->addValidators([$this->getFailedEntityValidator()]);
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(DtoSavingFailedException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->saveSingleDto($dto, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnFinalizeFailed()
	{
		$dto = $this->getDto();

		$processingDataResult = $this->getProcessingDataResult();
		$processingDataResult->addError('Test error');

		$this->expectException(EntityProcessorException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->finalize([$dto->getEntity()], [$dto], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnEntityValidationFailed()
	{
		$dto = $this->getDto();

		$this->tested->addValidators([$this->getFailedEntityValidator()]);
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(EntityValidationFailedException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->saveSingleDto($dto, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnEntityIntegrityValidationFailed()
	{
		$dto = $this->getDto();

		$this->tested->addValidators([$this->getFailedEntityIntegrityValidator()]);
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(EntityIntegrityValidationFailedException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->saveSingleDto($dto, $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnDataIntegrityValidationFailed()
	{
		$dto = $this->getDto();

		$this->tested->addValidators([$this->getFailedDataIntegrityValidator()]);
		$this->tested->method('recalculateAfterDtoSaving')->willReturn(true);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(DataIntegrityValidationException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->finalize([$dto->getEntity()], [$dto], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnFinalRecalculationFailed()
	{
		$dto = $this->getDto();

		$this->tested->method('recalculateAfterDtoSaving')->willReturn(false);

		$processingDataResult = $this->getProcessingDataResult();

		$this->expectException(FinalRecalculationFailedException::class);
		\Closure::bind(
			function() use ($dto, $processingDataResult) {
				$this->finalize([$dto->getEntity()], [$dto], $processingDataResult, new EntityProcessorMetadata($this));

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testSuccessSaveSingleDto()
	{
		$dto = $this->getDto();
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$processingDataResult = $this->getProcessingDataResult();

		$tester = $this;

		\Closure::bind(
			function() use ($dto, $processingDataResult, $tester) {
				$entity = $this->saveSingleDto($dto, $processingDataResult, new EntityProcessorMetadata($this));

				$tester::assertEquals($dto->getEntity(), $entity, 'Метод saveSingleDto не вернул вообще или вернул неверную сущность');

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	/**
	 * Тест проверяет правильное распределение валидаторов по категориям
	 * @return void
	 * @throws \Demoniqus\EntityProcessor\Exception\EntityProcessorException
	 */
	public function testValidatorsSorting()
	{
		$this->tested->addValidators([$this->getFailedValidator()]);

		$tester = $this;

		\Closure::bind(
			function() use ($tester) {
				foreach ($this->validators as $category => $validators) {
					$expectedInterface = null;
					switch ($category) {
						case EntitySaverInterface::VALIDATOR_CATEGORY_DTO:
							$expectedInterface = DtoValidatorInterface::class;
							break;
						case EntitySaverInterface::VALIDATOR_CATEGORY_ENTITY:
							$expectedInterface = EntityValidatorInterface::class;
							break;
						case EntitySaverInterface::VALIDATOR_CATEGORY_ENTITY_INTEGRITY:
							$expectedInterface = EntityIntegrityValidatorInterface::class;
							break;
						case EntitySaverInterface::VALIDATOR_CATEGORY_DATA_INTEGRITY:
							$expectedInterface = DataIntegrityValidatorInterface::class;
							break;
						default:
							$tester::assertTrue(false, 'Неизвестный тип валидаторов. Невозможно выполнить тестирование');

					}
					foreach ($validators as $validator) {
						$tester::assertInstanceOf($expectedInterface, $validator, 'Нарушено распределение валидаторов по категориям');
					}
				}

			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	public function testCrashOnSaveSingleDtoThrowOtherException_singleEntity()
	{
		$dto = $this->getDto();
		$this->tested->method('saveDtoToEntity')->willThrowException(new SpecificTestException());

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($dto) {
				$this->save($dto);
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowOtherException_singleEntity()
	{
		$dto = $this->getDto();
		$this->tested->method('recalculateAfterDtoSaving')->willThrowException(new SpecificTestException());
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($dto) {
				$this->save($dto);
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnSaveSingleDtoThrowOtherException_entitiesSet()
	{
		$dto = $this->getDto();
		$this->tested->method('saveDtoToEntity')->willThrowException(new SpecificTestException());

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($dto) {
				$this->saveSet([$dto]);
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowOtherException_entitiesSet()
	{
		$dto = $this->getDto();
		$this->tested->method('recalculateAfterDtoSaving')->willThrowException(new SpecificTestException());
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$this->expectException(SpecificTestException::class);
		\Closure::bind(
			function() use ($dto) {
				$this->saveSet([$dto]);
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	public function testCrashOnSaveSingleDtoThrowEntityProcessorException_singleEntity()
	{
		$dto = $this->getDto();
		$this->tested->method('saveDtoToEntity')->willThrowException(new DtoSavingFailedException());

		$tester = $this;

		\Closure::bind(
			function() use ($dto, $tester) {
				$result = $this->save($dto);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(DtoSavingFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnSaveSingleDtoThrowEntityProcessorException_entitiesSet()
	{
		$dto = $this->getDto();
		$this->tested->method('saveDtoToEntity')->willThrowException(new DtoSavingFailedException());

		$tester = $this;

		\Closure::bind(
			function() use ($dto, $tester) {
				$result = $this->saveSet([$dto]);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(DtoSavingFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}

	public function testCrashOnFinalizeThrowEntityProcessorException_singleEntity()
	{
		$dto = $this->getDto();
		$this->tested->method('recalculateAfterDtoSaving')->willThrowException(new FinalRecalculationFailedException());
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$tester = $this;

		\Closure::bind(
			function() use ($dto, $tester) {
				$result = $this->save($dto);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(FinalRecalculationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
	public function testCrashOnFinalizeThrowEntityProcessorException_entitiesSet()
	{
		$dto = $this->getDto();
		$this->tested->method('recalculateAfterDtoSaving')->willThrowException(new FinalRecalculationFailedException());
		$this->tested->method('saveDtoToEntity')->willReturn($dto->getEntity());
		$this->tested->method('isCreating')->willReturn(false);
		$this->tested->method('isUpdating')->willReturn(true);
		$this->tested->method('detectEntityChanges')->willReturn(
			[
				get_class($dto->getEntity()),
				[]
			]
		);

		$tester = $this;

		\Closure::bind(
			function() use ($dto, $tester) {
				$result = $this->saveSet([$dto]);

				$tester::assertTrue($result->hasErrors(), 'Ожидалось получение ошибок');

				if ($result->hasErrors()) {
					$tester::assertCount(1, $result->getErrors());
					$tester::assertEquals(FinalRecalculationFailedException::class, $result->getErrors()[0] ?? '', 'Неожиданный класс ошибки');
				}
			},
			$this->tested,
			AbstractEntitySaver::class
		)->__invoke();
	}
//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
}