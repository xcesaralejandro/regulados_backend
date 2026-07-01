<?php

namespace App\Traits;

use App\Mail\AccessCodeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

trait HasAccessCode
{
  public const ACCESS_CODE_DURATION = 10;

  public function dispatchAccessCodeMail(): void
  {
    if (!$this->hasActiveAccessCode()) {
      $this->update([
        'access_code' => $this->generateAccessCode(),
        'access_code_expires_at' => Carbon::now()->addMinutes(self::ACCESS_CODE_DURATION),
      ]);
    }
    $this->sendAccessCodeEmail();
  }

  private function generateAccessCode(): string
  {
    $allowedLetters = 'ABCDEFGHSTUVXY';
    $allowedDigits = '23456789';
    $letter = substr(str_shuffle($allowedLetters), 0, 1);
    $digits = '';
    for ($i = 0; $i < 5; $i++) {
      $digits .= substr(str_shuffle($allowedDigits), 0, 1);
    }
    return $letter . $digits;
  }

  private function sendAccessCodeEmail(): void
  {
    Mail::to($this->email)->send(new AccessCodeMail($this));
  }

  public function hasActiveAccessCode(): bool
  {
    return $this->access_code
      && $this->access_code_expires_at
      && Carbon::parse($this->access_code_expires_at)->isFuture();
  }

  public function verifyAccessCode(string $code): bool
  {
    return $this->hasActiveAccessCode() && strcasecmp($this->access_code, $code) === 0;
  }
}
