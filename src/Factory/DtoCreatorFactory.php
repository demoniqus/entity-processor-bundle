<?php


namespace Demoniqus\EntityProcessor\Factory;


use Demoniqus\EntityProcessor\Interfaces\DtoCreatorFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoCreatorInterface;
use Demoniqus\EntityProcessor\Interfaces\DtoInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityClassExtractorInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityInterface;

final class DtoCreatorFactory implements DtoCreatorFactoryInterface
{
//region SECTION: Fields
    /**
     * @var DtoCreatorInterface[]
     */
    private array $creators = [];
	private EntityClassExtractorInterface $entityClassExtractor;
//endregion Fields

//region SECTION: Constructor
	public function __construct(
		EntityClassExtractorInterface $entityClassExtractor
	)
	{
		$this->entityClassExtractor = $entityClassExtractor;
	}
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
    public function addDtoCreator(string $entityClass, DtoCreatorInterface $dtoCreator): void
    {
        $this->creators[$entityClass] = $dtoCreator;
    }

    /**
     * @param EntityInterface $entity
     * @return DtoInterface
     * @throws \Exception
     */
    public function getDtoFromEntity(EntityInterface $entity): DtoInterface
    {
        $entityClass = $this->entityClassExtractor->getClass($entity);
        if (!isset($this->creators[$entityClass])) {
            throw new \Exception('Для класса \'' . $entityClass . '\' не зарегистрирован обработчик. Обратитесь к администратору.');
        }
        $dtoCreatorService = $this->creators[$entityClass];

        return $dtoCreatorService->fromEntity($entity);
    }
//endregion Getters/Setters
}