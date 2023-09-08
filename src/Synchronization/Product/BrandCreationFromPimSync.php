<?php

namespace App\Synchronization\Product;

use App\Entity\Brand;
use App\Mailer\SendEmail;
use App\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class BrandCreationFromPimSync
{
    
    protected $manager;

    public function __construct(
        protected LoggerInterface $logger,
        protected ManagerRegistry $managerRegistry,
        protected AkeneoConnector $akeneoConnector,
        protected SendEmail $sendEmail,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }

    
    public function synchronize()
    {
        $brands = $this->akeneoConnector->getAllOptionsAttribute('brand');
        foreach ($brands as $brand) {
            $brandDb = $this->manager->getRepository(Brand::class)->findOneByCode($brand['code']);
            if(!$brandDb) {
                $brandDb=new Brand();
                $brandDb->setName($this->getLabel($brand));
                $brandDb->setCode($brand['code']);
                $brandDb->setEnabled(false);
                $this->manager->persist($brandDb);
            }
        }
        $this->manager->flush();
    }


    public function getLabel($brand)
    {
        foreach($brand['labels'] as $label) {
            if(strlen($label)>0) {
                return $label;
            }
        }
        return ucfirst(strtolower($brand['code']));
    }

}
