<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\ImportPricingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportPricingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ImportPricing
{

    use TraitTimeUpdated;

    use TraitLoggable;

    public const Status_Created = 0;
    public const Status_ToConfirm = 1;
    public const Status_ToImport = 2;
    public const Status_Imported = 3;
    public const Status_Importing = 4;
    public const Status_Cancelled = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private array $content = [];

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $status = null;


    public $uploadedFile;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentHeader()
    {
        return count($this->content) > 0 ? array_keys($this->content[0]) : [];
    }


    public function getUsername(): ?string
    {
        return $this->user ? $this->user->getUserIdentifier() : null;
    }


    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(?array $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
