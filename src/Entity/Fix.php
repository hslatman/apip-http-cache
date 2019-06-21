<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\FixRepository")
 */
class Fix
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FixRelation", cascade={"persist", "remove"})
     */
    private $fix_relation;

    /**
     * @ORM\ManyToOne(targetEntity="FixGroup", inversedBy="Fix")
     */
    private $fix_group;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFixRelation(): ?FixRelation
    {
        return $this->fix_relation;
    }

    public function setFixRelation(?FixRelation $fix_relation): self
    {
        $this->fix_relation = $fix_relation;

        return $this;
    }

    public function getFixGroup(): ?FixGroup
    {
        return $this->fix_group;
    }

    public function setFixGroup(?FixGroup $fix_group): self
    {
        $this->fix_group = $fix_group;

        return $this;
    }
}
