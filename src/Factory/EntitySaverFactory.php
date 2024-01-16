<?php


namespace Demoniqus\EntityProcessor\Factory;


use Demoniqus\EntityProcessor\Interfaces\EntitySaverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntitySaverInterface;
use Demoniqus\EntityProcessor\Exception\SaverNotFoundException;
use Demoniqus\EntityProcessor\Interfaces\ServiceExtractorInterface;

final class EntitySaverFactory implements EntitySaverFactoryInterface
{
//region SECTION: Fields
	private ServiceExtractorInterface $serviceExtractor;

//endregion Fields

//region SECTION: Constructor
	public function __construct(ServiceExtractorInterface $serviceExtractor)
    {
		$this->serviceExtractor = $serviceExtractor;
	}
//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getSaver(string $saverClassName): EntitySaverInterface
    {
        if (empty($saverClassName)) {
            throw new \LogicException("Can't find saver without alias");
        }

        if (!$this->serviceExtractor->has($saverClassName)) {
            throw new SaverNotFoundException('Saver \'' . $saverClassName . '\' not found');
        }

        return $this->serviceExtractor->get($saverClassName);
    }

    /**
     * @inheritDoc
     */
    public function getSavers(array $saverClassNames): array
    {
        return array_map(
            function(string $className){return $this->getSaver($className);},
            $saverClassNames
        );
    }
//endregion Getters/Setters
}