<?php

namespace App\Service;

final class PasswordGenerator
{
    private const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
    private const DIGITS = '0123456789';
    private const SPECIAL_CHARS = '!@#$';
    private const MAX_SIMILARITY_PERC = 20;

    public function __construct(
        private readonly int $minLength = 8,
        private readonly int $maxLength = 12,
        private readonly array $diffStrings = [],
    ) {
    }

    public function generate(): string
    {
        $chars = self::LETTERS . mb_strtoupper(self::LETTERS) . self::DIGITS . self::SPECIAL_CHARS;

        do {
            $password = '';
            $hasLowercase = false;
            $hasUppercase = false;
            $hasDigit = false;
            $hasSpecialChar = false;

            $length = random_int($this->minLength, $this->maxLength);
            while ($length-- > 0) {
                $char = $chars[random_int(0, mb_strlen($chars) - 1)];
                $password .= $char;

                $hasLowercase = $hasLowercase || mb_strpos(self::LETTERS, $char) !== false;
                $hasUppercase = $hasUppercase || mb_strpos(mb_strtoupper(self::LETTERS), $char) !== false;
                $hasDigit = $hasDigit || mb_strpos(self::DIGITS, $char) !== false;
                $hasSpecialChar = $hasSpecialChar || mb_strpos(self::SPECIAL_CHARS, $char) !== false;
            }

            $passwordReady = $hasLowercase && $hasUppercase && $hasDigit && $hasSpecialChar;
            foreach ($this->diffStrings as $string) {
                similar_text($password, $string, $similarityPerc);
                $passwordReady = $passwordReady && $similarityPerc < self::MAX_SIMILARITY_PERC;
            }
        } while (!$passwordReady);

        return $password;
    }

    public function generateInt(int $length = 6): string
    {
        $password = '';
        for ($i = 0; $i < $length; ++$i) {
            $password .= self::DIGITS[random_int(0, mb_strlen(self::DIGITS) - 1)];
        }

        return $password;
    }
}
