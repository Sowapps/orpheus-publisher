<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher\Validation;

class ValidationError {
	private string $message;
	private array $extra;
	private ?string $domain;
	private int $severity;
	
	public function __construct(string $message, array $extra, ?string $domain, int $severity) {
		$this->message = $message;
		$this->extra = $extra;
		$this->domain = $domain;
		$this->severity = $severity;
	}
	
	public function getMessage(): string {
		return $this->message;
	}
	
	public function getExtra(): array {
		return $this->extra;
	}
	
	public function getDomain(): ?string {
		return $this->domain;
	}
	
	public function getSeverity(): int {
		return $this->severity;
	}
	
}
