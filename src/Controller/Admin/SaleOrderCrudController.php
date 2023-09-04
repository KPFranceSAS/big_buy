<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Helper\Admin\AdminCrudController;
use App\Helper\Utils\StringUtils;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
            TextField::new('orderNumber')->setDisabled(true),
            AssociationField::new('saleOrderLines')->setDisabled(true),
            DateTimeField::new('updatedAt')->hideOnForm(),
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
