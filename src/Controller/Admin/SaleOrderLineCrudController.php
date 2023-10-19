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
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

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
            TextField::new('shipmentNumber'),
            TextField::new('invoiceNumber'),
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

        $choiceStatuts = [
            'Open' => SaleOrderLine::STATUS_OPEN,
            'Waiting release'  => SaleOrderLine::STATUS_WAITING_RELEASE,
            'Released / waiting shipment' => SaleOrderLine::STATUS_RELEASED,
            'Picked and posted' => SaleOrderLine::STATUS_SENT_BY_WAREHOUSE,
            'Delivery confirmed' => SaleOrderLine::STATUS_CONFIRMED,
            'Invoice sent' => SaleOrderLine::STATUS_INVOICED,
            'Cancelled' => SaleOrderLine::STATUS_CANCELLED,
        ];

        
        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add('sku')
            ->add('bigBuyOrderLine')
            ->add('shipmentNumber')
            ->add('invoiceNumber')
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
