<?php

namespace Larapen\Honeypot;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;

class EncryptedTime
{
	protected CarbonInterface $carbon;
	protected string $encryptedTime;
	
	/**
	 * @throws \Exception
	 */
	public function __construct(string $encryptedTime)
	{
		$this->encryptedTime = $encryptedTime;
		
		try {
			$timestamp = Crypt::decryptString($encryptedTime);
		} catch (DecryptException $e) {
			throw new \Exception("Timestamp {$encryptedTime} is invalid");
		}
		
		if (!$this->isValidTimeStamp($timestamp)) {
			throw new \Exception("Timestamp {$timestamp} is invalid");
		}
		
		$this->carbon = Date::createFromTimestamp($timestamp);
	}
	
	/**
	 * @throws \Exception
	 */
	public static function create(DateTimeInterface $dateTime): static
	{
		$encryptedTime = Crypt::encryptString($dateTime->getTimestamp());
		
		return new static($encryptedTime);
	}
	
	public function isFuture(): bool
	{
		return $this->carbon->isFuture();
	}
	
	protected function isValidTimeStamp(string $timestamp): bool
	{
		if ((string)(int)$timestamp !== $timestamp) {
			return false;
		}
		
		if ($timestamp <= 0) {
			return false;
		}
		
		if ($timestamp >= PHP_INT_MAX) {
			return false;
		}
		
		return true;
	}
	
	public function __toString()
	{
		return $this->encryptedTime;
	}
}
