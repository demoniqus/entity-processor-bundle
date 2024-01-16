<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface ValidatorInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    public function addErrorSubscriber(ErrorSubscriberInterface $errorSubscriber): void;

    public function rejectErrorSubscriber(ErrorSubscriberInterface  $errorSubscriber): void;
//endregion Getters/Setters
}