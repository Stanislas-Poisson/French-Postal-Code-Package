<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The link between an INSEE commune code and the code that follows it at a given date.
 * It has no foreign key: codes can be reused, so a succession is found by code and date.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'commune_successions')]
#[ORM\Index(columns: ['from_code', 'effective_date'])]
class CommuneSuccession
{
    #[ORM\Column(name: 'effective_date', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $effectiveDate;

    #[ORM\Column(name: 'from_code', length: 5)]
    private string $fromCode;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(length: 32)]
    private string $kind;

    #[ORM\Column(name: 'to_code', length: 5, nullable: true)]
    private ?string $toCode = null;

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function getFromCode(): string
    {
        return $this->fromCode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * `absorbed`, `code_reused`, `deleted`, `renamed`, `replaced` or `split`.
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * Null when the commune disappears without a successor.
     */
    public function getToCode(): ?string
    {
        return $this->toCode;
    }
}
