<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\DepartmentRepository;

/**
 * A department, or an overseas collectivity (which has no region).
 */
#[ORM\Entity(repositoryClass: DepartmentRepository::class, readOnly: true)]
#[ORM\Table(name: 'departments')]
#[ORM\UniqueConstraint(columns: ['code', 'valid_from'])]
class Department
{
    #[ORM\Column(length: 3)]
    private string $code;

    /**
     * @var Collection<int, Commune>
     */
    #[ORM\OneToMany(targetEntity: Commune::class, mappedBy: 'department')]
    private Collection $communes;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'departments')]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?Region $region = null;

    #[ORM\Column]
    private string $slug;

    #[ORM\Column(length: 32)]
    private string $type;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $validTo = null;

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Collection<int, Commune>
     */
    public function getCommunes(): Collection
    {
        return $this->communes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * `department` or `overseas_collectivity`.
     */
    public function getType(): string
    {
        return $this->type;
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
