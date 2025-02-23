<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Repository\SaleOrderRepository;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleOrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SaleOrder
{

    public const STATUS_ERROR_CONFIRMED = -1;


    public const STATUS_OPEN = 1;

    public const STATUS_WAITING_RELEASE = 2;

    public const STATUS_RELEASED = 3;

    public const STATUS_SENT_BY_WAREHOUSE = 4;

    public const STATUS_CONFIRMED = 5;

    public const STATUS_INVOICED = 6;

    public const STATUS_MIXED = 7;


    use TraitTimeUpdated;

    use TraitLoggable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\OneToMany(mappedBy: 'saleOrder', targetEntity: SaleOrderLine::class, orphanRemoval: true)]
    private Collection $saleOrderLines;

    #[ORM\Column(length: 255)]
    private ?string $releaseDateString = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shipmentNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalCost = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalPrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $carrier = null;

    public function __construct()
    {
        $this->saleOrderLines = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->orderNumber;
    }


    #[ORM\PrePersist()]
    public function setDateReleaseFormat(): void
    {
        $this->releaseDateString = $this->releaseDate->format('Y-m-d H:i');
    }

    #[ORM\PrePersist()]
    #[ORM\PreUpdate()]
    public function recalculateTotal()
    {
        $this->totalCost = 0;
        $this->totalPrice = 0;
        foreach($this->saleOrderLines as $saleOrderLine) {
            $this->totalPrice += $saleOrderLine->getTotalPrice();
            $this->totalCost  += $saleOrderLine->getTotalCost();
        }
    }

    public function updateStatus()
    {
        $statusLine = [];
        foreach($this->saleOrderLines as $saleOrderLine){
            if(!in_array($saleOrderLine->getStatus(), $statusLine)){
                $statusLine[]= $saleOrderLine->getStatus();                            
            }
        }
        if(count($statusLine)==1){
            $this->status = $statusLine[0];
        } else {
            $this->status = self::STATUS_MIXED;
        }
    }
    

    public function getMargin()
    {
        return $this->getTotalPrice() - $this->getTotalCost();
    }


    public function getMarginRate()
    {
        return $this->getTotalPrice() == 0 ? '0%' : round(($this->getMargin()/$this->getTotalPrice())*100, 2).'%';
    }



    public function getLineSequence($lineSequence, $sku)
    {
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getLineNumber()==$lineSequence && $sku == $saleOrderLine->getSku()) {
                return $saleOrderLine;
            }
        }
        return null;
    }


    public function getAllLinesSequenceAndSku($lineSequence, $sku)
    {
        $saleLines = [];

        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getLineNumber()==$lineSequence && $sku == $saleOrderLine->getSku()) {
                $saleLines[]= $saleOrderLine;
            }
        }
        return $saleLines;
    }





    public function getLineSequenceForSkuPrice($sku, $price)
    {
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getPrice()==$price && $sku == $saleOrderLine->getSku()) {
                return $saleOrderLine->getLineNumber();
            }
        }
        return null;
    }





    public function getArrivalTime(): DateTime
    {
        $dateArrival = DateTime::createFromFormat('Y-m-d H:i', $this->releaseDateString);
        $dateArrival->add(new DateInterval('P1D'));
        $dateArrival->setTime(10, 0);
        return $dateArrival;
    }

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

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

    /**
     * @return Collection<int, SaleOrderLine>
     */
    public function getSaleOrderLines(): Collection
    {
        return $this->saleOrderLines;
    }

    public function addSaleOrderLine(SaleOrderLine $saleOrderLine): static
    {
        if (!$this->saleOrderLines->contains($saleOrderLine)) {
            $this->saleOrderLines->add($saleOrderLine);
            $saleOrderLine->setSaleOrder($this);
        }

        return $this;
    }

    public function removeSaleOrderLine(SaleOrderLine $saleOrderLine): static
    {
        if ($this->saleOrderLines->removeElement($saleOrderLine)) {
            // set the owning side to null (unless already changed)
            if ($saleOrderLine->getSaleOrder() === $this) {
                $saleOrderLine->setSaleOrder(null);
            }
        }

        return $this;
    }

    public function getReleaseDateString(): ?string
    {
        return $this->releaseDateString;
    }

    public function setReleaseDateString(string $releaseDateString): static
    {
        $this->releaseDateString = $releaseDateString;

        return $this;
    }

    public function getShipmentNumber(): ?string
    {
        return $this->shipmentNumber;
    }

    public function setShipmentNumber(?string $shipmentNumber): static
    {
        $this->shipmentNumber = $shipmentNumber;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getTotalCost(): ?float
    {
        return $this->totalCost;
    }

    public function setTotalCost(?float $totalCost): static
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): static
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }


}
