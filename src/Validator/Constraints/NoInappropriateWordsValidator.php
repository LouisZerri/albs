<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoInappropriateWordsValidator extends ConstraintValidator
{
    // Liste des mots interdits
    private const FORBIDDEN_WORDS = [
        'admin',
        'administrator',
        'moderator',
        'moderateur',
        'root',
        'system',
        'support',
        'test',
        'caca',
        'pipi',
        'merde',
        'connard',
        'salope',
        'putain',
        'pute',
        'nazi',
        'hitler',
        'fuck',
        'shit',
        'ass',
        'bitch',
        'dick',
        'penis',
        'vagina',
        'sex',
        'porn',
        'xxx',
        'fdp',
        'ntm',
        'enculé',
        'encule',
        'bite',
        'couille',
        'chatte',
        'con',
        'connasse',
        'salaud',
        'batard',
    ];

    // Suites de clavier interdites
    private const KEYBOARD_SEQUENCES = [
        'azerty',
        'qwerty',
        'azertyuiop',
        'qwertyuiop',
        'asdfgh',
        'zxcvbn',
        '123456',
        'abcdef',
        '098765',
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoInappropriateWords) {
            throw new UnexpectedTypeException($constraint, NoInappropriateWords::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $valueLower = mb_strtolower($value);

        // 1. Vérifier les mots interdits
        foreach (self::FORBIDDEN_WORDS as $word) {
            $pattern = $this->createPattern($word);
            if (preg_match($pattern, $valueLower)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
                return;
            }
        }

        // 2. Vérifier les caractères répétitifs (aaa, zzz, 111, etc.)
        if (preg_match('/(.)\1{2,}/', $valueLower)) {
            $this->context->buildViolation('Le pseudo ne peut pas contenir plus de 2 caractères identiques consécutifs (ex: aaa, zzz).')
                ->addViolation();
            return;
        }

        // 3. Vérifier les patterns répétitifs (ababab, 123123123, etc.)
        if (preg_match('/(.{2,})\1{2,}/', $valueLower)) {
            $this->context->buildViolation('Le pseudo ne peut pas contenir de motifs répétitifs (ex: ababab, 123123).')
                ->addViolation();
            return;
        }

        // 4. Vérifier les suites de clavier
        foreach (self::KEYBOARD_SEQUENCES as $sequence) {
            if (str_contains($valueLower, $sequence)) {
                $this->context->buildViolation('Le pseudo ne peut pas contenir de suites de clavier (ex: azerty, qwerty).')
                    ->addViolation();
                return;
            }
        }

        // 5. Vérifier les suites logiques (abc, 123, 987, etc.)
        if ($this->hasSequentialChars($valueLower)) {
            $this->context->buildViolation('Le pseudo ne peut pas contenir de suites logiques (ex: abc, 123, 987).')
                ->addViolation();
            return;
        }
    }

    /**
     * Crée un pattern regex pour détecter les variantes avec chiffres
     */
    private function createPattern(string $word): string
    {
        $replacements = [
            'a' => '[a@4]',
            'e' => '[e3]',
            'i' => '[i1!]',
            'o' => '[o0]',
            's' => '[s5$]',
            't' => '[t7]',
            'l' => '[l1]',
            'g' => '[g9]',
            'b' => '[b8]',
        ];

        $pattern = '';
        for ($i = 0; $i < mb_strlen($word); $i++) {
            $char = mb_substr($word, $i, 1);
            $pattern .= $replacements[$char] ?? $char;
        }

        return '/' . $pattern . '/i';
    }

    /**
     * Détecte les suites de caractères consécutifs (abc, 123, xyz, 987, etc.)
     */
    private function hasSequentialChars(string $value): bool
    {
        $minSequenceLength = 4; // Minimum 4 caractères consécutifs

        for ($i = 0; $i < strlen($value) - $minSequenceLength + 1; $i++) {
            $sequence = substr($value, $i, $minSequenceLength);
            
            // Vérifier si c'est une suite croissante ou décroissante
            $isAscending = true;
            $isDescending = true;
            
            for ($j = 0; $j < $minSequenceLength - 1; $j++) {
                $current = ord($sequence[$j]);
                $next = ord($sequence[$j + 1]);
                
                if ($next !== $current + 1) {
                    $isAscending = false;
                }
                if ($next !== $current - 1) {
                    $isDescending = false;
                }
            }
            
            if ($isAscending || $isDescending) {
                return true;
            }
        }

        return false;
    }
}