<?php

namespace App\BusinessCentral\Connector;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BusinessCentralConnector
{


    public static function getItinialesCompany(string $company)
    {
        if($company==BusinessCentralConnector::INIA) {
           
            return 'INIA';
        } elseif($company==BusinessCentralConnector::KIT_PERSONALIZACION_SPORT) {
            return 'KPS';
        } elseif($company==BusinessCentralConnector::KP_FRANCE) {
            return 'KPF';
        } elseif($company==BusinessCentralConnector::KP_UK) {
            return 'KPUK';
        }
    }


    public const STOCK_LAROCA = "LAROCA";
    public const STOCK_PLUK = "3PLUK";

    public const KP_FRANCE = "KP FRANCE";
    public const GADGET_IBERIA = "GADGET IBERIA SL";
    public const KIT_PERSONALIZACION_SPORT = "KIT PERSONALIZACION SPORT SL";
    public const INIA = "INIA SLU";
    public const KP_UK = "KP UK";


    public const EP_CUSTOMER_PAYMENT_JOURNALS = "customerPaymentJournals";

    public const EP_CUSTOMER_PAYMENTS = "customerPayments";
    
    public const EP_ACCOUNT = "accounts";

    public const EP_COMPANIES = "companies";

    public const EP_CUSTOMERS = "customers";

    public const EP_FEES_TAXES = "FeesAndTaxes";

    public const EP_ITEMS = "items";

    public const EP_BUNDLE_CONTENT = "billOfMaterials";

    public const EP_SALES_ORDERS = "salesOrders";

    public const EP_SALES_ORDERS_LINE = "salesOrderLines";

    public const EP_STATUS_ORDERS = "statusOrders";
    
    public const EP_STOCK_PRODUCTS = "itemAvailabilities";

    public const EP_ITEM_PRICES = "SalesPrices";

    public const EP_ITEM_DISCOUNT= "SalesLineDiscount";

    protected $logger;

    protected $debugger;

    /**@var Symfony\Component\HttpClient\CurlHttpClient */
    protected $client;

    protected $companyId;

    protected $urlBase;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $urlBC,
        string $loginBC,
        string $passwordBC,
    ) {
        $this->logger = $logger;
        $this->urlBase =  $urlBC . '/api/v1.0/';
        $this->client = $client->withOptions([
            'base_uri' => $this->urlBase,
            'headers'  => [
                'User-Agent'    => 'PlatformB2B',
                'Authorization' => "Basic " . base64_encode("$loginBC:$passwordBC"),
            ],
        ]);
    }


    abstract public function getCompanyIntegration();

    abstract public function getDefaultWarehouse();

    public function getJournalPayment()
    {
        return 'MOLLIE';
    }


    public function getAccountNumberBank()
    {
        return "2024";
    }


    abstract public function getVendorNumberMollie();

    abstract public function getAccountNumberForExpedition();
    


    public function doDeleteRequest(string $endPoint)
    {
        $response = $this->client->request(
            'DELETE',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
        );

        return $response->getStatusCode() == '204';
    }



    public function doPostRequest(string $endPoint, array $json, array $query = [])
    {
        
        $this->logger->info(json_encode($json));
        $response = $this->client->request(
            'POST',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'query' =>  $query,
                'json' => $json,
                'headers' => [
                    'Content-Type' => 'application/json',
                    "Accept-Language" => 'en-US',
                ],
            ]
        );

        return json_decode($response->getContent(), true);
    }


    public function doGetRequest(string $endPoint, array $query = [], $headers = null)
    {
        if(!$headers) {
            $headers =   [
                  "Accept-Language" => 'en-US',
                  'Content-Type' => 'application/json'
            ];
        }
 
        $endPoint = $endPoint === self::EP_COMPANIES
            ? self::EP_COMPANIES
            : self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint;



        $response = $this->client->request(
            'GET',
            $endPoint,
            [
                'query' =>  $query,
                'headers' => $headers
            ]
        );
        
        return json_decode($response->getContent(), true);
    }




    public function doPatchRequest(string $endPoint, string $etag, array $json, array $query = [])
    {


        $this->logger->info("PATCH >>>>".json_encode($json));

        $response = $this->client->request(
            'PATCH',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'query' =>  $query,
                'json' => $json,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'If-Match' => $etag,
                ],
            ]
        );

        return json_decode($response->getContent(), true);
    }



    public function downloadStream(string $endPoint): string
    {
        $response = $this->client->request(
            'GET',
            $endPoint,
            [
                'debug' => $this->debugger
            ]
        );
        return $response->getContent();
    }


    public function getElementsByArray(
        string $type,
        ?string $filters,
        bool $all = false,
        array $paramSupps = []
    ) : ?array {
        $query = [];
        if ($filters) {
            $query = [
                '$filter' => $filters
            ];
        }
        foreach ($paramSupps as $keyParam => $valParam) {
            $query[$keyParam] = $valParam;
        }
        


        if ($all) {
            $reponse = $this->doGetRequest($type, $query);
            $items = [];
            $continue=true;
            while ($continue) {
                $items = array_merge($items, $reponse ['value']);
                if(array_key_exists('@odata.nextLink', $reponse)) {

                    $url = str_replace($this->urlBase, '', $reponse['@odata.nextLink']);

                    $response = $this->client->request(
                        'GET',
                        $url,
                        [
                            'headers' => [
                                "Accept-Language" => 'en-US',
                                'Content-Type' => 'application/json'
                            ],
                        ]
                    );
                    
                    $reponse = json_decode($response->getContent(), true);
                } else {
                    $continue = false;
                }
            }
            return $items;
        } else {
            $reponse = $this->doGetRequest($type, $query);
            $items =  $reponse ['value'];
            if (count($items) > 0) {
                return reset($items);
            } else {
                return null;
            }
        }
    }


    public function getAll(string $type)
    {
        return $this->getElementsByArray($type, null, true);
    }


    public function getElementByNumber(
        string $type,
        string $number,
        string $filter = 'number',
        array $paramSupps = []
    ) {
        return $this->getElementsByArray($type, "$filter eq '$number'", false, $paramSupps);
    }





    public function getElementById(
        string $type,
        string $id,
        array $paramSupps = []
    ) {
        try {
            $item =  $this->doGetRequest($type . '(' . $id . ')', $paramSupps);
            return $item;
        } catch (Exception $e) {
            throw new Exception("No $type in the database with id equal to $id. You need to add a corelation");
        }
    }

    /**
     * Company
     */
    public function getCompanyName()
    {
        return $this->getCompanyIntegration();
    }


    public function getCompanyId(): string
    {
        if (!$this->companyId) {
            $this->selectCompany($this->getCompanyIntegration());
        }
        return $this->companyId;
    }

    public function selectCompany(string $name): string
    {
        $companies = $this->getCompanies();
        foreach ($companies as $company) {
            if (strtoupper($company['name']) == $name) {
                $this->companyId = $company['id'];
                return $company['id'];
            }
        }
        throw new Exception($name . ' not found');
    }


    public function getCompanies()
    {
        return $this->getAll(self::EP_COMPANIES);
    }



    /**
     * Account
     */
    public function getAccountByNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_ACCOUNT, $number);
    }

    public function getAccountForExpedition(): ?array
    {
        return $this->getAccountByNumber($this->getAccountNumberForExpedition());
    }


    public function getFeesAndTaxes()
    {
        return $this->getAll(self::EP_FEES_TAXES);
    }


    public function getPricesSkuPerGroup($sku, $group = 'PVP-ES')
    {
        $filter = "SalesType eq 'Customer Price Group' and SalesCode eq '$group' and MinimumQuantity eq 0 and ItemNo eq '$sku'";

        return $this->getElementsByArray(self::EP_ITEM_PRICES, $filter, true);
    }


    public function getPricesSkuPerCustomer($sku, $customerNumber)
    {
        $filter = "SalesType eq 'Customer' and SalesCode eq '$customerNumber' and MinimumQuantity eq 0 and ItemNo eq '$sku'";

        return $this->getElementsByArray(self::EP_ITEM_PRICES, $filter, true);
    }

   


    /**
     * Item
     */
    public function getItemByNumber(string $sku)
    {
        return $this->getElementByNumber(self::EP_ITEMS, $sku);
    }

    public function getItem(string $id)
    {
        return $this->getElementById(self::EP_ITEMS, $id);
    }


    public function getLastUpdatedItems(DateTime $from): array
    {
        $fromString = $from->format('Y-m-d\TH:i:s.u\Z');
        return $this->getElementsByArray(self::EP_ITEMS, "lastModifiedDateTime gt $fromString", true);
    }


    /**
     * Cusotmer
     */

    public function getShippingAddressByCustomerNumber($customerNumber)
    {
        return $this->getElementsByArray(
            'ShippingAddress',
            "customerNo eq '$customerNumber'",
            true
        );
    }
    

    public function getAllCustomerPaymentJournals()
    {
        return $this->getElementsByArray(
            self::EP_CUSTOMER_PAYMENT_JOURNALS,
            null,
            true
        );
    }

    public function getAllCustomers(): array
    {
        return $this->getElementsByArray(
            self::EP_CUSTOMERS,
            null,
            true
        );
    }


    public function getCustomerByNumber(string $number): ?array
    {
        return $this->getElementsByArray(self::EP_CUSTOMERS, "number eq '$number'", false, ['$expand' => 'customerFinancialDetails,salespersons']);
    }



    public function getLastUpdatedCustomers(DateTime $from): array
    {
        $fromString = $from->format('Y-m-d\TH:i:s.u\Z');
        return $this->getElementsByArray(self::EP_CUSTOMERS, "lastModifiedDateTime gt $fromString", true, ['$expand' => 'customerFinancialDetails,salespersons']);
    }


    public function getLastUpdatedCustomersByDate(DateTime $from): array
    {
        $fromString = $from->format('Y-m-d');
        return $this->getElementsByArray(self::EP_CUSTOMERS, "lastDateModified gt $fromString", true, ['$expand' => 'customerFinancialDetails,salespersons']);
    }



    public function getAllCustomersToSend(): array
    {
        return $this->getElementsByArray(self::EP_CUSTOMERS, "sendToB2B eq true", true, ['$expand' => 'customerFinancialDetails,salespersons']);
    }

   

    public function createReservation($reservation)
    {
        return $this->doPostRequest(
            'CreateReserves',
            $reservation
        );
    }




    public function getGeneralJournalByCode(string $code): ?array
    {
        return $this->getElementsByArray(
            'journals',
            "code eq '$code'",
        );
    }



    public function createJournalLine(string $id, array $line): ?array
    {
        return $this->doPostRequest(
            'journals(' . $id . ')/journalLines',
            $line
        );
    }
 
 
 
    public function updateJournalLine(string $idJournal, string $idJournalLine, string $etag, array $journalLine): ?array
    {
        $this->logger->info("PATCH >>>>".json_encode($journalLine));
        return $this->doPatchRequest('journals(' . $idJournal . ")/journalLines(".$idJournalLine.")", '*', $journalLine);
    }


    
   



    public function getAllTaxes(): ?array
    {
        return $this->getElementsByArray(self::EP_FEES_TAXES, null, true);
    }



    public function getTaxesByCodeAndByFeeType($code, $feeType): ?array
    {
        return $this->getElementsByArray(self::EP_FEES_TAXES, "FeeType eq '$feeType' and Code eq '$code'");
    }




    public function getComponentsBundle(string $sku)
    {
        return $this->getElementsByArray(
            self::EP_BUNDLE_CONTENT,
            "parentItemNo eq '$sku'",
            true
        );
    }




    public function getStockAvailabilityPerProduct(string $sku)
    {
        return $this->getElementsByArray(
            self::EP_STOCK_PRODUCTS,
            "no eq '$sku'",
        );
    }


    public function getAllSalePrices()
    {
        return $this->getElementsByArray(
            self::EP_ITEM_PRICES,
            null,
            true
        );
    }


    public function getAllSalePricesPerProduct($itemNumber)
    {
        return $this->getElementsByArray(
            self::EP_ITEM_PRICES,
            "ItemNo eq '$itemNumber'",
            true
        );
    }


    

    public function getAllSaleDiscount()
    {
        return $this->getElementsByArray(
            self::EP_ITEM_DISCOUNT,
            null,
            true
        );
    }


    public function getAllSaleDiscountPerProduct($itemNumber)
    {
        return $this->getElementsByArray(
            self::EP_ITEM_DISCOUNT,
            "Code eq '$itemNumber' and Type eq 'Item'",
            true
        );
    }



    public function getAllSaleDiscountPerItemDiscountGroup($itemDiscGroup)
    {
        return $this->getElementsByArray(
            self::EP_ITEM_DISCOUNT,
            "Code eq '$itemDiscGroup' and Type eq 'Item Disc. Group'",
            true
        );
    }





    /**
     * Sale order
     */
    public function createSaleOrder(array $order): ?array
    {
        return $this->doPostRequest(
            self::EP_SALES_ORDERS,
            $order
        );
    }



    /**
     * Sale order
     */

    public function updateSaleOrder(string $id, string $etag, array $order): ?array
    {
        return $this->doPatchRequest(
            self::EP_SALES_ORDERS . '(' . $id . ')',
            $etag,
            $order
        );
    }




    /**
    * Sale order
    */

    public function updateSaleOrderLine(string $idOrder, string $idOrderLine, string $etag, array $orderLine): ?array
    {
        return $this->doPatchRequest(
            self::EP_SALES_ORDERS . '(' . $idOrder . ')/'.self::EP_SALES_ORDERS_LINE."('".$idOrderLine."')",
            $etag,
            $orderLine
        );
    }


    public function getAllSaleLines(string $id): ?array
    {
        return $this->getElementsByArray(
            self::EP_SALES_ORDERS.'('.$id.')/'.self::EP_SALES_ORDERS_LINE,
            null,
            true
        );
    }




    public function getFullSaleOrder(string $id): ?array
    {
        return  $this->getElementById(
            self::EP_SALES_ORDERS,
            $id,
            ['$expand' => 'salesOrderLines,customer']
        );
    }

    public function getFullSaleOrderByNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_ORDERS,
            $number,
            'number',
            ['$expand' => 'salesOrderLines,customer']
        );
    }


    public function getStatusOrderByNumber(string $number): ?array
    {
        $query = [
            '$filter' => "number eq '$number'",
            '$expand' => 'statusOrderLines'
        ];
        $headers =  ['Content-Type' => 'application/json'];
        $reponse = $this->doGetRequest(self::EP_STATUS_ORDERS, $query, $headers);
        $items =  $reponse ['value'];
        if (count($items) > 0) {
            return reset($items);
        } else {
            return null;
        }
    }


    public function getSaleOrder(string $id): ?array
    {
        return $this->getElementById(self::EP_SALES_ORDERS, $id);
    }

    public function getSaleOrderByNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number);
    }

    public function getSaleOrderByExternalNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number, 'externalDocumentNumber');
    }


    

    public function getSaleOrderByExternalNumberAndCustomer(string $number, string $customer)
    {
        return $this->getElementsByArray(
            self::EP_SALES_ORDERS,
            "externalDocumentNumber eq '$number' and customerNumber eq '$customer'",
            false
        );
    }
}
