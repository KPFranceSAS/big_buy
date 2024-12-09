<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpUkConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::KP_UK;
    }


    public function getAccountNumberForExpedition()
    {
        return '7591001';
    }

    public function getDefaultWarehouse()
    {
        return BusinessCentralConnector::STOCK_LAROCA;
    }
}
