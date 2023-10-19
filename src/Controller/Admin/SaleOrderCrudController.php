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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
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
            FormField::addTab('General')->setIcon('fas fa-shopping-cart'),
            TextField::new('orderNumber'),

            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
            DateTimeField::new('releaseDate'),
            DateTimeField::new('arrivalTime'),
            NumberField::new('totalCost')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('totalPrice')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('margin')->setDisabled(true)->setThousandsSeparator(''),
            TextField::new('marginRate')->setColumns(3),
            IntegerField::new('status')->setTemplatePath('admin/fields/saleOrder/status.html.twig'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
            FormField::addTab('Lines')->setIcon('fas fa-barcode'),
            AssociationField::new('saleOrderLines')->setTemplatePath('admin/fields/saleOrder/lines.html.twig')->onlyOnDetail(),
            
            FormField::addTab('Logs')->setIcon('fas fa-history'),
            ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig')->onlyOnDetail(),
        ];
    }






    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::DELETE)
            ->disable(Action::EDIT)
            ->disable(Action::NEW)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel("Show");
            });
    }


}
