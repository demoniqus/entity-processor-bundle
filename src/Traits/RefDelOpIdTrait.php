<?php


namespace Demoniqus\EntityProcessor\Traits;

use Demoniqus\EntityProcessor\Interfaces\EntityInterface;

/**
 * @property EntityInterface|null $delOp
 */
trait RefDelOpIdTrait
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
    public function getDelOp(): ?EntityInterface
    {
        return $this->delOp;
    }

    /**
     * @param EntityInterface|null $delOp
     *
     * @return RefDelOpIdTrait
     */
    public function setDelOp(?EntityInterface $delOp): self
    {
        $this->delOp = $delOp;

        return $this;
    }
//endregion Getters/Setters
}