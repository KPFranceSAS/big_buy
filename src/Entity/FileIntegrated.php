<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\FileIntegratedRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileIntegratedRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FileIntegrated
{

    use TraitTimeUpdated;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }
}
