<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CityRepository;

/**
 * A postal entry: a commune and one of its postal codes, with its own GPS point, for example "37200 Tours".
 * It is the row to reference by a foreign key: its identifier is stable and never reused.
 */
#[ORM\Entity(repositoryClass: CityRepository::class, readOnly: true)]
#[ORM\Table(name: 'cities')]
#[ORM\UniqueConstraint(columns: ['commune_id', 'postal_code', 'valid_from'])]
#[ORM\Index(columns: ['postal_code'])]
class City
{
    #[ORM\Column(name: 'address_count', type: Types::INTEGER, options: ['default' => 0])]
    private int $addressCount = 0;

    #[ORM\ManyToOne(targetEntity: Commune::class, inversedBy: 'cities')]
    #[ORM\JoinColumn(name: 'commune_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Commune $commune;

    #[ORM\Column(name: 'coordinate_source', length: 32, nullable: true)]
    private ?string $coordinateSource = null;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'postal_code', length: 5)]
    private string $postalCode;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'replaced_by_city_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?City $replacedBy = null;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $validTo = null;

    public function getAddressCount(): int
    {
        return $this->addressCount;
    }

    public function getCommune(): Commune
    {
        return $this->commune;
    }

    /**
     * `ban`, `nominatim` or `commune_centre`.
     */
    public function getCoordinateSource(): ?string
    {
        return $this->coordinateSource;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * The city that replaces this one once its validity is closed.
     */
    public function getReplacedBy(): ?City
    {
        return $this->replacedBy;
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
