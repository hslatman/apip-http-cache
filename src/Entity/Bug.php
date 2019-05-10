<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\BugRepository")
 */
class Bug
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
    private $reporter;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): ?string
    {
        return $this->reporter;
    }

    public function setReporter(string $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }
}
