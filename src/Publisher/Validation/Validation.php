<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher\Validation;

use Orpheus\Exception\UserException;

class Validation {
	
	/** @var ValidationError[] */
	protected array $errors = [];
	
	public function merge(Validation $validation): static {
		$this->errors = array_merge($this->errors, $validation->getErrors());
		
		return $this;
	}
	
	public function addValidationError(ValidationError $error): static {
		$this->errors[] = $error;
		
		return $this;
	}
	
	public function addError(string|UserException $error, ?string $domain = null, int $severity = 1): static {
		$extra = [];
		if( $error instanceof UserException ) {
			$extra = $error->getExtraData();
			$domain ??= $error->getDomain();
			
			$error = $error->getMessage();
		}
		
		return $this->addValidationError(new ValidationError($error, $extra, $domain, $severity));
	}
	
	public function getReports(): array {
		$reports = [];
		foreach($this->errors as $error) {
			$reports[] = [
				'code' => $error->getMessage(),
				'report' => t($error->getMessage(), $error->getDomain()),
				'domain' => $error->getDomain(),
				'severity' => $error->getSeverity()
			];
		}
		return $reports;
	}
	
	public function getErrors(): array {
		return $this->errors;
	}
	
	public function isValid(): bool {
		return !$this->hasErrors();
	}
	
	public function hasErrors(): bool {
		return !!$this->errors;
	}
	
}
