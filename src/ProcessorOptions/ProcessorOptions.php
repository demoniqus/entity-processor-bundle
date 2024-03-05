<?php


namespace Demoniqus\EntityProcessor\ProcessorOptions;


use Demoniqus\EntityProcessor\Interfaces\ProcessorOptionsInterface;

/**
 * Класс предназначен для передачи от пользователя различных опций, настраивающих поведение EntityProcessor'a.
 * Под пользователем может пониматься как физический пользователь, так и другой скрипт, вызывающий текущий EntityProcessor.
 */
final class ProcessorOptions implements ProcessorOptionsInterface
{
//region SECTION: Fields
    private array $options = [];
//endregion Fields

//region SECTION: Constructor

//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public

//endregion Public

//region SECTION: Getters/Setters
    public function setOption(string $optionName, $value, $object = null): self
    {
        if ($object) {
            $this->options[$optionName][spl_object_hash($object)] = $value;
        }
        else {
            $this->options[$optionName] = $value;
        }

        return $this;
    }

    public function getOption(string $optionName, $object = null)
    {
        return $object ?
			(
				is_string($object)?
					$this->options[$optionName][$object] ?? null :
					$this->options[$optionName][spl_object_hash($object)] ?? null
			) :
            $this->options[$optionName] ?? null
            ;
    }
//endregion Getters/Setters
}