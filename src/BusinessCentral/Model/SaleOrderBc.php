<?php

namespace App\BusinessCentral\Model;

use App\BusinessCentral\Model\PostalAddress;

class SaleOrderBc
{
    public const STATUS_OPEN = "Open";
    public const STATUS_RELEASED = "Released";
    public const STATUS_PENDING_APPROVAL = "Pending_Approval";
    public const STATUS_PENDING_PREPAYMENT = "Pending_Prepayment";

    public $shippingPostalAddress;


    public $shipToName;

    public $billToName;

    public $customerNumber;

    public $customerId;

    public $externalDocumentNumber;

    public $number;

    public $locationCode = 'LAROCA';

    public $orderOrigin = "MARKETPLACE";

    public $pendingToShip = true;

    public $currencyCode;

    public $pricesIncludeTax = false;

    public $paymentTermsId;

    public $paymentMethodCode;

    public $shipmentMethodId;

    public $shippingAgent;

    public $shippingAgentService;

    public $partialShipping;

    public $requestedDeliveryDate;

    public $discountAmount;

    public $discountAppliedBeforeTax;

    public $totalAmountExcludingTax;

    public $totalTaxAmount;

    public $totalAmountIncludingTax;

    public $status;

    public $phoneNumber;

    public $email;

    public $URLEtiqueta;


    public function __construct()
    {
        $this->shippingPostalAddress = new PostalAddress();
        
        $this->salesLines = [];
    }

    public $salesLines = [];



    public function transformToArray(): array
    {
        $transformArray = ['salesOrderLines' => []];
        foreach ($this as $key => $value) {
            if ($key == 'salesLines') {
                foreach ($this->salesLines as $saleLine) {
                    $transformArray['salesOrderLines'][] = $saleLine->transformToArray();
                }
            } elseif (in_array($key, ['shippingPostalAddress'])) {
                $transformArray[$key] = $value->transformToArray();
            } elseif ($value !== null) {
                $transformArray[$key] = $value;
            }
        }
        return $transformArray;
    }
}
