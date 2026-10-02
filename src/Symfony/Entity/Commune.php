<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CommuneRepository;

/**
 * A commune, or a municipal arrondissement. A commune that was merged or renamed stays, with a `valid_to` date.
 */
#[ORM\Entity(repositoryClass: CommuneRepository::class, readOnly: true)]
#[ORM\Table(name: 'communes')]
#[ORM\UniqueConstraint(columns: ['insee_code', 'valid_from'])]
#[ORM\Index(columns: ['insee_code'])]
class Commune
{
    #[ORM\Column(name: 'centre_latitude', type: Types::FLOAT, nullable: true)]
    private ?float $centreLatitude = null;

    #[ORM\Column(name: 'centre_longitude', type: Types::FLOAT, nullable: true)]
    private ?float $centreLongitude = null;

    /**
     * @var Collection<int, City>
     */
    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'commune')]
    private Collection $cities;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'communes')]
    #[ORM\JoinColumn(name: 'department_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?Department $department = null;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(name: 'insee_code', length: 5)]
    private string $inseeCode;

    #[ORM\Column(length: 4)]
    private string $kind;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $slug;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $validTo = null;

    public function getCentreLatitude(): ?float
    {
        return $this->centreLatitude;
    }

    public function getCentreLongitude(): ?float
    {
        return $this->centreLongitude;
    }

    /**
     * @return Collection<int, City>
     */
    public function getCities(): Collection
    {
        return $this->cities;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getInseeCode(): string
    {
        return $this->inseeCode;
    }

    /**
     * `COM` for a commune, `ARM` for a municipal arrondissement.
     */
    public function getKind(): string
    {
        return $this->kind;
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

    public function isCurrent(): bool
    {
        return ! $this->validTo instanceof DateTimeImmutable;
    }
}
