<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity("sku")]
#[Gedmo\Loggable]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TraitTimeUpdated;

    use TraitLoggable;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sku = null;

    #[ORM\Column(length: 255)]
    private ?string $nameErp = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Versioned]
    private ?float $price = null;

    #[ORM\Column]
    #[Gedmo\Versioned]
    private ?bool $enabled = true;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brand $brand = null;

    #[ORM\Column(nullable: true)]
    private ?float $publicPrice = null;

    #[ORM\Column]
    private ?float $canonDigital = 0;

    #[ORM\Column(length: 255)]
    private ?string $vatCode = 'VAT21';

    #[ORM\Column]
    private ?bool $bundle = false;

    #[ORM\Column]
    private ?bool $activeInBc = false;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Versioned]
    private ?float $resellerPrice = null;

    #[ORM\Column(nullable: true)]
    private ?float $costPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $stockLaRoca = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Versioned]
    private ?bool $forcePrice = false;

    public function getId(): ?int
    {
        return $this->id;
    }




    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if($this->price && $this->price < $this->costPrice && $this->forcePrice == false) {
            $context->buildViolation('If you want to sell with a price lower than cost price, you have to mark price as forced in product page')
                ->atPath('price')
                ->addViolation();
        }
    }



    public function getFinalPriceBigBuy(): ?string
    {
        if($this->price) {
            if(($this->costPrice > $this->price && $this->forcePrice)|| ($this->costPrice < $this->price)) {
                return $this->price;
            }
        }
        return $this->resellerPrice;
    }



    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getNameErp(): ?string
    {
        return $this->nameErp;
    }

    public function setNameErp(string $nameErp): static
    {
        $this->nameErp = $nameErp;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getPublicPrice(): ?float
    {
        return $this->publicPrice;
    }

    public function setPublicPrice(?float $publicPrice): static
    {
        $this->publicPrice = $publicPrice;

        return $this;
    }

    public function getCanonDigital(): ?float
    {
        return $this->canonDigital;
    }

    public function setCanonDigital(float $canonDigital): static
    {
        $this->canonDigital = $canonDigital;

        return $this;
    }

    public function getVatCode(): ?string
    {
        return $this->vatCode;
    }

    public function setVatCode(string $vatCode): static
    {
        $this->vatCode = $vatCode;

        return $this;
    }

    public function isBundle(): ?bool
    {
        return $this->bundle;
    }

    public function setBundle(bool $bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    public function isActiveInBc(): ?bool
    {
        return $this->activeInBc;
    }

    public function setActiveInBc(bool $activeInBc): static
    {
        $this->activeInBc = $activeInBc;

        return $this;
    }

    public function getResellerPrice(): ?float
    {
        return $this->resellerPrice;
    }

    public function setResellerPrice(?float $resellerPrice): static
    {
        $this->resellerPrice = $resellerPrice;

        return $this;
    }

    public function getCostPrice(): ?float
    {
        return $this->costPrice;
    }

    public function setCostPrice(?float $costPrice): static
    {
        $this->costPrice = $costPrice;

        return $this;
    }

    public function getStockLaRoca(): ?int
    {
        return $this->stockLaRoca;
    }

    public function setStockLaRoca(?int $stockLaRoca): static
    {
        $this->stockLaRoca = $stockLaRoca;

        return $this;
    }

    public function isForcePrice(): ?bool
    {
        return $this->forcePrice;
    }

    public function setForcePrice(?bool $forcePrice): static
    {
        $this->forcePrice = $forcePrice;

        return $this;
    }
}
