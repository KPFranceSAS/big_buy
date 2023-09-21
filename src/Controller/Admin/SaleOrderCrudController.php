<?php

namespace App\Controller\Admin;

use App\Entity\SaleOrder;
use App\Helper\Admin\AdminCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SaleOrderCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return SaleOrder::class;
    }


    public function getDefautOrder(): array
    {
        return ['releaseDate' => "DESC"];
    }


  



    public function configureFields(string $pageName): iterable
    {
        return [
            
            DateTimeField::new('releaseDate')->setDisabled(true),
            IntegerField::new('status')->setDisabled(true)->setTemplatePath('admin/fields/saleOrder/status.html.twig'),
            AssociationField::new('saleOrderLines')->setDisabled(true)->setTemplatePath('admin/fields/saleOrder/lines.html.twig')->onlyOnDetail(),
            DateTimeField::new('updatedAt')->hideOnForm(),
            ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig')->onlyOnDetail(),
        ];
    }






    public function configureActions(Actions $actions): Actions
    {


      
        return $actions
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::DELETE)
            ->disable(Action::EDIT)
            ->disable(Action::NEW);
            
    }


}
