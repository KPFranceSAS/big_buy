<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpUkConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return self::KP_UK;
    }


    public function getAccountNumberForExpedition()
    {
        return '00101';
    }


    public function getVendorNumberMollie()
    {
        return 'KPUK000016';   
    }

    public function getDefaultWarehouse()
    {
        return BusinessCentralConnector::STOCK_PLUK;
    }

}
