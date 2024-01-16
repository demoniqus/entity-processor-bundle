<?php


namespace Demoniqus\EntityProcessor\Interfaces;


interface ProcessorOptionsInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    /**
     * @param string $optionName - наименование опции
     * @param        $value - значение опции
     * @param        $object - объект, для которого конкретно эта опция устанавливается. Если null, опция может быть
     *                       использована несколькими сервисами и/или для всех обрабатываемых сервисом объектов
     * @return ProcessorOptionsInterface
     */
    function setOption(string $optionName, $value, $object = null): ProcessorOptionsInterface;

    /**
     * @param string $optionName
     * @param        $object
     * @return mixed
     */
    function getOption(string $optionName, $object = null);
//endregion Getters/Setters
}