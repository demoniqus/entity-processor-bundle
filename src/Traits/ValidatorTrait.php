<?php


namespace Demoniqus\EntityProcessor\Traits;


trait ValidatorTrait
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor

//region SECTION: Protected
    /**
     * @param string[] $errors
     */
    protected function addErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }
//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters

//endregion Getters/Setters
}