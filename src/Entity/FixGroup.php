<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\FixGroupRepository")
 */
class FixGroup
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
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Fix", mappedBy="fix_group")
     */
    private $fixes;

    public function __construct()
    {
        $this->fixes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection|Fix[]
     */
    public function getFix(): Collection
    {
        return $this->fixes;
    }

    public function addFix(Fix $fix): self
    {
        if (!$this->fixes->contains($fix)) {
            $this->fixes[] = $fix;
            $fix->setFixGroup($this);
        }

        return $this;
    }

    public function removeFix(Fix $fix): self
    {
        if ($this->fixes->contains($fix)) {
            $this->fixes->removeElement($fix);
            // set the owning side to null (unless already changed)
            if ($fix->getFixGroup() === $this) {
                $fix->setFixGroup(null);
            }
        }

        return $this;
    }
}
