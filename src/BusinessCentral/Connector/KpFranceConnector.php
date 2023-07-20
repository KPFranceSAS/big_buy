<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpFranceConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return self::KP_FRANCE;
    }


    public function getAccountNumberForExpedition()
    {
        return '758000';
    }


    public function getVendorNumberMollie()
    {
        return '20560';   
    }


    public function getDefaultWarehouse()
    {
        return BusinessCentralConnector::STOCK_LAROCA;
    }
}
