<?php

namespace App\Controller\Admin;

use App\Entity\SaleOrderLine;
use App\Helper\Admin\AdminCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SaleOrderLineCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return SaleOrderLine::class;
    }


    public function getDefautOrder(): array
    {
        return ['createdAt' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku'),
            TextField::new('name'),
            TextField::new('orderNumber'),
            TextField::new('bigBuyOrderLine'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('releaseDate'),
            NumberField::new('price')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('quantity')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('totalPrice')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('totalPrice')->setDisabled(true)->setThousandsSeparator(''),
            NumberField::new('margin')->setDisabled(true)->setThousandsSeparator(''),
            TextField::new('marginRate')->setDisabled(true)->setColumns(3),
            IntegerField::new('status')->setTemplatePath('admin/fields/saleOrder/status.html.twig'),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('sku')
            ->add('bigBuyOrderLine')
        ;
    }



    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE)
            ->disable(Action::EDIT)
            ->disable(Action::NEW);
    }

}
