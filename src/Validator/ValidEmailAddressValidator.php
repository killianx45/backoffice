<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidEmailAddressValidator extends ConstraintValidator
{
    /**
     * List of disposable/temporary email domains to block
     */
    private array $disposableDomains = [
        'tempmail.com',
        'guerrillamail.com',
        'mailinator.com',
        '10minutemail.com',
        'throwaway.email',
        'temp-mail.org',
        'getnada.com',
        'maildrop.cc',
        'trashmail.com',
        'yopmail.com',
        'fakeinbox.com',
        'mintemail.com',
        'sharklasers.com',
        'guerrillamail.info',
        'grr.la',
        'guerrillamail.biz',
        'guerrillamail.de',
        'spam4.me',
        'getairmail.com',
        'tmpnator.live',
        'mohmal.com',
        'emailondeck.com',
        'throwawaymail.com',
        'mailnesia.com',
        'mailcatch.com',
        'dispostable.com',
        'guerrillamailblock.com',
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidEmailAddress) {
            throw new UnexpectedTypeException($constraint, ValidEmailAddress::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // Basic email format validation (already done by Email constraint, but double-check)
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
            return;
        }

        // Extract domain
        $parts = explode('@', $value);
        if (count($parts) !== 2) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
            return;
        }

        $domain = strtolower($parts[1]);

        // Check if domain is in disposable list
        if (in_array($domain, $this->disposableDomains, true)) {
            $this->context->buildViolation($constraint->disposableEmailMessage)
                ->setParameter('{{ domain }}', $domain)
                ->addViolation();
            return;
        }

        // Check DNS MX records
        if (!$this->checkMXRecords($domain)) {
            $this->context->buildViolation($constraint->invalidDomainMessage)
                ->setParameter('{{ domain }}', $domain)
                ->addViolation();
            return;
        }
    }

    /**
     * Check if domain has valid MX records
     */
    private function checkMXRecords(string $domain): bool
    {
        // First check if domain exists with checkdnsrr
        if (!checkdnsrr($domain, 'MX')) {
            // Fallback: check if domain has A record (some domains use A record for mail)
            if (!checkdnsrr($domain, 'A')) {
                return false;
            }
        }

        // Verify MX records exist
        if (!getmxrr($domain, $mxhosts)) {
            // No MX records, check if A record exists as fallback
            $records = dns_get_record($domain, DNS_A);
            return !empty($records);
        }

        return true;
    }
}
