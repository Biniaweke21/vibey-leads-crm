<?php

namespace App\Enums;

/**
 * Union of every document type's statuses (single documents table):
 *   proposal: draft, sent, approved, rejected
 *   contract: draft, sent, signed, expired
 *   invoice:  draft, sent, partially-paid, paid, overdue
 */
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Signed = 'signed';
    case Expired = 'expired';
    case PartiallyPaid = 'partially-paid';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::PartiallyPaid => 'Partially Paid',
            default => ucfirst($this->value),
        };
    }

    public static function forType(DocumentType $type): array
    {
        return match ($type) {
            DocumentType::Proposal => [self::Draft, self::Sent, self::Approved, self::Rejected],
            DocumentType::Contract => [self::Draft, self::Sent, self::Signed, self::Expired],
            DocumentType::Invoice => [self::Draft, self::Sent, self::PartiallyPaid, self::Paid, self::Overdue],
        };
    }
}
