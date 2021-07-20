<?php

namespace App\Entity;

class MilkYieldRecord
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTimeInterface
     */
    private $calvingDate;

    /**
     * @var int
     */
    private $daysInMilk;

    /**
     * @var float
     */
    private $totalMilkRecord;

    /**
     * @var float
     */
    private $expectedMilkYield;

    /**
     * @var float
     */
    private $upperLimit;

    /**
     * @var float
     */
    private $lowerLimit;

    /**
     * @var string
     */
    private $feedback;

    /**
     * @var int
     */
    private $farmId;

    /**
     * @var bool
     */
    private $farmRelocation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCalvingDate(): ?\DateTimeInterface
    {
        return $this->calvingDate;
    }

    public function setCalvingDate(\DateTimeInterface $calvingDate): self
    {
        $this->calvingDate = $calvingDate;

        return $this;
    }

    public function getDaysInMilk(): ?int
    {
        return $this->daysInMilk;
    }

    public function setDaysInMilk(int $daysInMilk): self
    {
        $this->daysInMilk = $daysInMilk;

        return $this;
    }

    public function getTotalMilkRecord(): ?float
    {
        return $this->totalMilkRecord;
    }

    public function setTotalMilkRecord(float $totalMilkRecord): self
    {
        $this->totalMilkRecord = $totalMilkRecord;

        return $this;
    }

    public function getExpectedMilkYield(): ?float
    {
        return $this->expectedMilkYield;
    }

    public function setExpectedMilkYield(float $expectedMilkYield): self
    {
        $this->expectedMilkYield = $expectedMilkYield;

        return $this;
    }

    public function getUpperLimit(): ?float
    {
        return $this->upperLimit;
    }

    public function setUpperLimit(float $upperLimit): self
    {
        $this->upperLimit = $upperLimit;

        return $this;
    }

    public function getLowerLimit(): ?float
    {
        return $this->lowerLimit;
    }

    public function setLowerLimit(float $lowerLimit): self
    {
        $this->lowerLimit = $lowerLimit;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }


    public function getFarmId(): ?int
    {
        return $this->farmId;
    }

    public function setFarmId(int $farmId): self
    {
        $this->farmId = $farmId;

        return $this;
    }

    public function getFarmRelocation(): ?bool
    {
        return $this->farmRelocation;
    }

    public function setFarmRelocation(bool $farmRelocation): self
    {
        $this->farmRelocation = $farmRelocation;

        return $this;
    }
}
