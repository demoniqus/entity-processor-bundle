<?php

namespace Demoniqus\EntityProcessor\Traits;

trait ErrorsSubscriberTrait
{
//region SECTION: Fields
	private $errors = [];

	private $uniqueErrors = [];
//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 

//endregion Protected

//region SECTION: Public
	public function addError($error, $identifier = null, $key = null): self
	{
		if (null === $key) {
			$message = (string)$error;
			if (!array_key_exists($message, $this->uniqueErrors)) {
				$this->errors[] = $error;
				$this->uniqueErrors[$message] = 0;
			}
			$this->uniqueErrors[$message]++; //считаем, сколько раз встретилась данная ошибка
		}
		else {
			$this->errors[$key] = $error;
		}

		return $this;
	}
//endregion Public

//region SECTION: Private
	private function isValid(): bool
	{
		return $this->hasErrors();
	}
//endregion Private

//region SECTION: Getters/Setters 
	public function getErrors(): array
	{
		return $this->errors;
	}

	public function hasErrors(): bool
	{
		return \count($this->errors) === 0;
	}
//endregion Getters/Setters
}