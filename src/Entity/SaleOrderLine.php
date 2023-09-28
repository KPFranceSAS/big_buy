<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\SaleOrderLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleOrderLineRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SaleOrderLine
{

    use TraitTimeUpdated;

    use TraitLoggable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sku = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?int $lineNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bigBuyOrderLine = null;

    #[ORM\ManyToOne(inversedBy: 'saleOrderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SaleOrder $saleOrder = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $unitCost = null;

    #[ORM\ManyToOne(inversedBy: 'saleOrderLines')]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getOrderStatus(){
        return $this->saleOrder->getStatus();
    }


    public function getOrderNumber(){
        return $this->saleOrder->getOrderNumber();
    }

    public function getReleaseDate(){
        return $this->saleOrder->getReleaseDate();
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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public function setLineNumber(?int $lineNumber): static
    {
        $this->lineNumber = $lineNumber;

        return $this;
    }

    public function getBigBuyOrderLine(): ?string
    {
        return $this->bigBuyOrderLine;
    }

    public function setBigBuyOrderLine(?string $bigBuyOrderLine): static
    {
        $this->bigBuyOrderLine = $bigBuyOrderLine;

        return $this;
    }

    public function getSaleOrder(): ?SaleOrder
    {
        return $this->saleOrder;
    }

    public function setSaleOrder(?SaleOrder $saleOrder): static
    {
        $this->saleOrder = $saleOrder;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUnitCost(): ?float
    {
        return $this->unitCost;
    }

    public function setUnitCost(?float $unitCost): static
    {
        $this->unitCost = $unitCost;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }



    public function getBrand()
    {
        return $this->product ? strtoupper($this->product->getBrand()->getName()) : '';
    }

    public function getTotalPrice()
    {
        return $this->quantity*$this->price;
    }

    public function getTotalCost()
    {
        return $this->quantity*$this->unitCost;
    }


    public function getMargin()
    {
        return $this->getTotalPrice() - $this->getTotalCost();
    }


    public function getMarginRate()
    {
        return round(($this->getMargin()/$this->getTotalPrice())*100, 2).'%';
    }




}
