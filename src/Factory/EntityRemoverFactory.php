<?php


namespace Demoniqus\EntityProcessor\Factory;


use Demoniqus\EntityProcessor\Interfaces\EntityRemoverFactoryInterface;
use Demoniqus\EntityProcessor\Interfaces\EntityRemoverInterface;
use Demoniqus\EntityProcessor\Exception\RemoverNotFoundException;
use Demoniqus\EntityProcessor\Interfaces\ServiceExtractorInterface;

final class EntityRemoverFactory implements EntityRemoverFactoryInterface
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
    /**
     * @param string $removerClassName
     * @return EntityRemoverInterface
     * @noinspection PhpIncompatibleReturnTypeInspection
     * @throws RemoverNotFoundException
     */
    public function getRemover(string $removerClassName): EntityRemoverInterface
    {
        if (empty($removerClassName)) {
            throw new \LogicException('Can\'t find remover without alias');
        }

        if (!$this->serviceExtractor->has($removerClassName)) {
            throw new RemoverNotFoundException('Remover \'' . $removerClassName . '\' not found');
        }

        return $this->serviceExtractor->get($removerClassName);
    }

    /**
     * @param array $removerClassNames
     * @return array|EntityRemoverInterface[]
     * @throws RemoverNotFoundException
     */
    public function getRemovers(array $removerClassNames): array
    {
        return array_map(
            function(string $removerClassName){return $this->getRemover($removerClassName);},
            $removerClassNames
        );
    }
//endregion Getters/Setters
}