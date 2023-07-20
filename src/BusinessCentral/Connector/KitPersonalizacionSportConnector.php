<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KitPersonalizacionSportConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }


    public function getAccountNumberForExpedition()
    {
        return '7591001';
    }


    public function getVendorNumberMollie()
    {
        return '21201';   
    }


    public function getDefaultWarehouse()
    {
        return BusinessCentralConnector::STOCK_LAROCA;
    }

}
