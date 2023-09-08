<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Helper\Admin\AdminCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }


    public function getDefautOrder(): array
    {
        return ["enabled"=> 'DESC', "code"=>'ASC'];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('code')
            ->add('name')
            ->add('enabled')
        ;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            
            TextField::new('code')->setDisabled(true),
            TextField::new('name')->setDisabled(true),
            AssociationField::new('products')->setDisabled(true)->setSortable(false),
            BooleanField::new('enabled')->renderAsSwitch(false)->setDisabled(true),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }




    public function configureActions(Actions $actions): Actions
    {


        $actionReactivate = Action::new('reactivate', 'Enable')
        ->linkToCrudAction('reactivate')
        ->setIcon('fas fa-toggle-on')
        ->displayIf(static function ($entity) {
            return $entity->isEnabled()==false;
        });

        $actionDesactivate = Action::new('deactivate', 'Disable')
        ->linkToCrudAction('deactivate')
        ->setIcon('fas fa-toggle-off')
        ->displayIf(static function ($entity) {
            return $entity->isEnabled();
        });

        return $actions
            ->disable(Action::DETAIL)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, $actionDesactivate)
            ->add(Crud::PAGE_INDEX, $actionReactivate)
            
        ;
    }




    public function deactivate(AdminContext $context)
    {
        $brand = $context->getEntity()->getInstance();
        $manager = $this->container->get('doctrine')->getManager();
       
        $brand->addLog('Product disabled');
        $brand->setEnabled(false);
        foreach($brand->getProducts() as $product) {
            $product->setEnabled(false);
        }
        $manager->flush();
        $this->addFlash('success', 'Brand and products has been disabled');
        return $this->redirect($context->getReferrer());
    }


    public function reactivate(AdminContext $context)
    {
        $brand = $context->getEntity()->getInstance();
        $manager = $this->container->get('doctrine')->getManager();
       
        $brand->addLog('Product enabled');
        $brand->setEnabled(true);
        foreach($brand->getProducts() as $product) {
            if($product->isActiveInBc()) {
                $product->setEnabled(true);
            }
        }
        $manager->flush();
        $this->addFlash('success', 'Brand and products has been activated');
        return $this->redirect($context->getReferrer());
    }




  


}
