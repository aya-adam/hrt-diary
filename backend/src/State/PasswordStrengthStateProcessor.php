<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Dto\PasswordStrengthPayload;
use App\Api\Resource\PasswordStrength;
use RuntimeException;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;

class PasswordStrengthStateProcessor implements ProcessorInterface
{
    private const PASSWORD_STRENGTH_MESSAGES = [
        // PasswordStrength::STRENGTH_VERY_WEAK    
        0 => [
            'This password is very weak and cannot be accepted.',
            'This password could be guessed within seconds, so please choose a different one.',
            'This password appears on lists of commonly used passwords and is not allowed.',
            'Your account would be at serious risk, so this password was rejected.',
            'Anyone trying common passwords would crack this instantly, so it cannot be used.',
        ],
        // PasswordStrength::STRENGTH_WEAK
        1 => [
            'This password is too weak and was not accepted.',
            'It follows a predictable pattern that attackers try first, so please choose another one.',
            'Please use a longer password with a mix of different character types.',
            'Automated tools could crack this password quickly, so it cannot be used.',
            'This password offers too little protection, so please choose a stronger one.',
        ],
        // PasswordStrength::STRENGTH_MEDIUM
        2 => [
            'This password is not strong enough yet, so it was not accepted.',
            'A determined attacker could still crack this password, so please make it stronger.',
            'Try making it longer to meet the minimum strength requirement.',
            'Avoid names, dates and dictionary words, then try again.',
            'You are almost there, but a few more changes are needed before this password is accepted.',
        ],
        // PasswordStrength::STRENGTH_STRONG
        3 => [
            'This password is strong and has been accepted.',
            'Good choice, this password offers solid protection for your account.',
            'Password accepted. It would take a long time for an attacker to crack it.',
            'This password is accepted, and only a little more length would make it excellent.',
            'Password accepted. Remember to use it for this account only.',
        ],
        // PasswordStrength::STRENGTH_VERY_STRONG
        4 => [
            'Excellent, this very strong password has been accepted.',
            'Password accepted. Guessing it would be practically impossible with current technology.',
            'Great choice, your password is long, complex and unpredictable.',
            'This password gives your account an excellent level of protection and has been accepted.',
            'Password accepted. Remember to store it safely, ideally in a password manager.',
        ],
    ];

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PasswordStrengthPayload
    {
        if (!$data instanceof PasswordStrength) {
            throw new RuntimeException(sprintf('Invalid data type "%s". %s required.', get_debug_type($data), PasswordStrength::class));
        }

        $passwordEstimatedStrength = PasswordStrengthValidator::estimateStrength($data->password);

        $messages = self::PASSWORD_STRENGTH_MESSAGES[$passwordEstimatedStrength];

        return new PasswordStrengthPayload($passwordEstimatedStrength, $messages[array_rand($messages)]);
    }
}
