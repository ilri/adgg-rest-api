<?php

namespace App\Entity;

use App\Entity\Traits\{
    AdditionalAttributesTrait,
    AdministrativeDivisionsTrait,
    CountryTrait,
    CreatedTrait,
    IdentifiableTrait,
    ODKIdentifiableTrait,
    OrganisationTrait,
    UpdatedTrait
};
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FarmRepository;

/**
 * Farm
 *
 * @ORM\Table(name="core_farm", indexes={@ORM\Index(name="country_id", columns={"country_id", "region_id", "district_id", "ward_id", "village_id"}), @ORM\Index(name="farm_type", columns={"farm_type"}), @ORM\Index(name="org_id", columns={"org_id", "client_id"}), @ORM\Index(name="IDX_6AF31370F92F3E70", columns={"country_id"})})
 * @ORM\Entity(repositoryClass=FarmRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Farm
{
    use AdministrativeDivisionsTrait;
    use AdditionalAttributesTrait;
    use CountryTrait;
    use CreatedTrait;
    use IdentifiableTrait;
    use ODKIdentifiableTrait;
    use OrganisationTrait;
    use UpdatedTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=128, nullable=true)
     *
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="reg_date", type="date", nullable=true)
     *
     */
    private $regDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="farmer_name", type="string", length=128, nullable=true)
     *
     */
    private $farmerName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     *
     */
    private $phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     *
     */
    private $email;

    /**
     * @var int|null
     *
     * @ORM\Column(name="field_agent_id", type="integer", nullable=true)
     *
     */
    private $fieldAgentId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="project", type="string", length=128, nullable=true)
     *
     */
    private $project;

    /**
     * @var string|null
     *
     * @ORM\Column(name="farm_type", type="string", length=30, nullable=true)
     *
     */
    private $farmType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gender_code", type="string", length=10, nullable=true)
     *
     */
    private $genderCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="farmer_is_hh_head", type="boolean", nullable=false, options={"default"="1"})
     *
     */
    private $farmerIsHhHead = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default"="1"})
     *
     */
    private $isActive = true;

    /**
     * @var string|null
     *
     * @ORM\Column(name="latitude", type="decimal", precision=13, scale=8, nullable=true)
     *
     */
    private $latitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="longitude", type="decimal", precision=13, scale=8, nullable=true)
     *
     */
    private $longitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="map_address", type="string", length=255, nullable=true)
     *
     */
    private $mapAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="latlng", type="string", length=0, nullable=true)
     *
     */
    private $latlng;

    /**
     * @var string|null
     *
     * @ORM\Column(name="odk_code", type="string", length=255, nullable=true)
     *
     */
    private $odkCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="odk_farm_code", type="string", length=128, nullable=true, options={"comment"="Farm code from Harrison db for odk form versions 1.5 and below"})
     *
     */
    private $odkFarmCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    private $isDeleted;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     *
     */
    private $deletedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="deleted_by", type="integer", nullable=true)
     * })
     */
    private $deletedBy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="migration_id", type="string", length=255, nullable=true, options={"comment"="This is the migrationSouce plus primary key from migration source table of the record e.g KLBA_001"})
     */
    private $migrationId;


    /**
     * @var string|null
     *
     * @ORM\Column(name="unique_id_odk", type="string", length=45, nullable=true)
     *
     */
    private $uniqueIdOdk;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRegDate(): ?\DateTimeInterface
    {
        return $this->regDate;
    }

    public function setRegDate(?\DateTimeInterface $regDate): self
    {
        $this->regDate = $regDate;

        return $this;
    }

    public function getFarmerName(): ?string
    {
        return $this->farmerName;
    }

    public function setFarmerName(?string $farmerName): self
    {
        $this->farmerName = $farmerName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFieldAgentId(): ?int
    {
        return $this->fieldAgentId;
    }

    public function setFieldAgentId(?int $fieldAgentId): self
    {
        $this->fieldAgentId = $fieldAgentId;

        return $this;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function setProject(?string $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getFarmType(): ?string
    {
        return $this->farmType;
    }

    public function setFarmType(?string $farmType): self
    {
        $this->farmType = $farmType;

        return $this;
    }

    public function getGenderCode(): ?string
    {
        return $this->genderCode;
    }

    public function setGenderCode(?string $genderCode): self
    {
        $this->genderCode = $genderCode;

        return $this;
    }

    public function getFarmerIsHhHead(): ?bool
    {
        return $this->farmerIsHhHead;
    }

    public function setFarmerIsHhHead(bool $farmerIsHhHead): self
    {
        $this->farmerIsHhHead = $farmerIsHhHead;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getMapAddress(): ?string
    {
        return $this->mapAddress;
    }

    public function setMapAddress(?string $mapAddress): self
    {
        $this->mapAddress = $mapAddress;

        return $this;
    }

    public function getLatlng(): ?string
    {
        return $this->latlng;
    }

    public function setLatlng(?string $latlng): self
    {
        $this->latlng = $latlng;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }



    public function getOdkCode(): ?string
    {
        return $this->odkCode;
    }

    public function setOdkCode(?string $odkCode): self
    {
        $this->odkCode = $odkCode;

        return $this;
    }

    public function getOdkFarmCode(): ?string
    {
        return $this->odkFarmCode;
    }

    public function setOdkFarmCode(?string $odkFarmCode): self
    {
        $this->odkFarmCode = $odkFarmCode;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedBy(): ?int
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?int $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    public function getMigrationId(): ?string
    {
        return $this->migrationId;
    }

    public function setMigrationId(?string $migrationId): self
    {
        $this->migrationId = $migrationId;

        return $this;
    }

    public function getUniqueIdOdk(): ?string
    {
        return $this->uniqueIdOdk;
    }

    public function setUniqueIdOdk(?string $uniqueIdOdk): self
    {
        $this->uniqueIdOdk = $uniqueIdOdk;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }
}
