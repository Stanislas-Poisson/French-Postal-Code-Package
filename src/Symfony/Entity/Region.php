<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\RegionRepository;

/**
 * A French region.
 *
 * The entities are not final: Doctrine builds proxies that extend them, to load a relation only when it is read.
 * They are read-only for an application. The rows are written by `french-postal-code:load`, with their original identifiers.
 */
#[ORM\Entity(repositoryClass: RegionRepository::class, readOnly: true)]
#[ORM\Table(name: 'regions')]
#[ORM\UniqueConstraint(columns: ['code', 'valid_from'])]
class Region
{
    #[ORM\Column(length: 3)]
    private string $code;

    /**
     * @var Collection<int, Department>
     */
    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: 'region')]
    private Collection $departments;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $slug;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $validTo = null;

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getValidFrom(): DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?DateTimeImmutable
    {
        return $this->validTo;
    }

    /**
     * Whether the row is valid today: a row is never deleted, its validity is closed.
     */
    public function isCurrent(): bool
    {
        return ! $this->validTo instanceof DateTimeImmutable;
    }
}
