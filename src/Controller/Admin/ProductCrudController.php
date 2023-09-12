<?php

namespace App\Controller\Admin;

use App\Entity\Product;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ProductCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }


    public function getDefautOrder(): array
    {
        return ["enabled"=>'DESC', 'sku' => "ASC"];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('sku')
            ->add('price')
            ->add('enabled')
            ->add('activeInBc')
            ->add('stock')
            ->add('brand')
            ->add('forcePrice')
        ;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Product')
                ->setIcon('fas fa-barcode'),
            TextField::new('sku')->setDisabled(true)->setColumns(4),
            TextField::new('nameErp')->setDisabled(true)->setColumns(4),
            AssociationField::new('brand')->setDisabled(true)->setSortable(false)->setColumns(4),
            NumberField::new('stockLaRoca', 'Stock')->setDisabled(true)->setThousandsSeparator('')->setColumns(4),
            BooleanField::new('activeInBc')->renderAsSwitch(false)->setDisabled(true)->setColumns(4),
            
            FormField::addPanel('Common Price')
                ->setIcon('fas fa-money-bill'),
            NumberField::new('costPrice', 'Cost price')->setDisabled(true)->setThousandsSeparator('')->setColumns(4),
            NumberField::new('publicPrice', 'PVP-ES')->setDisabled(true)->setThousandsSeparator('')->setColumns(4),
            NumberField::new('resellerPrice', 'PVD-ES')->setDisabled(true)->setThousandsSeparator('')->setColumns(4),
            FormField::addPanel('Big Buy Price')->setIcon('fas fa-money-bill-alt'),
            NumberField::new('price')->setDisabled(false)->setThousandsSeparator('')->setColumns(4),
            BooleanField::new('forcePrice')->renderAsSwitch(false)->setColumns(4),
            NumberField::new('finalPriceBigBuy')->hideOnForm(),
            BooleanField::new('enabled')->renderAsSwitch(false)->setDisabled(true)->hideOnForm(),
            
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }



    public function export(
        FilterFactory $filterFactory,
        AdminContext $context,
        EntityFactory $entityFactory,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $directory = $params->get('kernel.project_dir') . '/var/export/';
        $fileName = u('Export_' . $this->getName() . '_' . date('Ymd_His'))->snake() . '.csv';
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $writer = $this->createWriter($fields, $directory . $fileName);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $pageSize = 1000;
        $currentPage = 1;
        $query = $queryBuilder
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery();

        $batchs = [];
        foreach ($query->toIterable() as $element) {
            $batchs[] = $element;
            $currentPage++;
            if (($currentPage % $pageSize) === 0) {
                $logger->info("Exported  $currentPage ");
                $entities = $entityFactory->createCollection($context->getEntity(), $batchs);
                $entityFactory->processFieldsForAll($entities, $fields);

                foreach ($entities->getIterator() as $entityArray) {
                    $this->addDataToWriter($writer, $entityArray);
                }
                foreach ($batchs as $batch) {
                    $this->container->get('doctrine')->getManager()->detach($batch);
                }
                unset($batchs);
                unset($entities);
                $batchs = [];
                $this->container->get('doctrine')->getManager()->clear();
            }
        }

        $logger->info("Exported  $currentPage ");
        $entities = $entityFactory->createCollection($context->getEntity(), $batchs);
        $entityFactory->processFieldsForAll($entities, $fields);

        foreach ($entities->getIterator() as $entityArray) {
            $this->addDataToWriter($writer, $entityArray);
        }
        foreach ($batchs as $batch) {
            $this->container->get('doctrine')->getManager()->detach($batch);
        }
        unset($batchs);
        unset($entities);
        $this->container->get('doctrine')->getManager()->clear();
        $writer->close();
        $logger->info('Finish ');

        $response = new BinaryFileResponse($directory . $fileName);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        return $response;
    }




    protected function addDataToWriter(Writer $writer, EntityDto $entity)
    {
        $fieldsEntity = $entity->getFields();
        $cellDatas = [];
        foreach ($fieldsEntity as $fieldEntity) {
            $cellDatas[] = WriterEntityFactory::createCell($fieldEntity->getFormattedValue());
        }
        $singleRowData = WriterEntityFactory::createRow($cellDatas);
        $writer->addRow($singleRowData);
    }



    protected function createWriter(FieldCollection $fields, string $filePath): Writer
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $writer->openToFile($filePath);
        $cellHeaders = [];
        foreach ($fields as $field) {
            $label = strlen($field->getLabel()) > 0
                ? $field->getLabel()
                : StringUtils::humanizeString($field->getProperty());
            $cellHeaders[] = WriterEntityFactory::createCell($label);
        }
        $singleRow = WriterEntityFactory::createRow($cellHeaders);
        $writer->addRow($singleRow);
        return $writer;
    }



    protected function getFieldsExport(): FieldCollection
    {
        return FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
    }



    public function configureActions(Actions $actions): Actions
    {


        $actionReactivate = Action::new('reactivate', 'Enable')
        ->linkToCrudAction('reactivate')
        ->setIcon('fas fa-toggle-on')
        ->displayIf(static function ($entity) {
            return $entity->isEnabled()==false && $entity->isActiveInBc() && $entity->getBrand()->isEnabled();
        });

        $actionReactivateList = clone($actionReactivate);
        $actionReactivateList->setLabel(false);

        $actionDesactivate = Action::new('deactivate', 'Disable')
        ->linkToCrudAction('deactivate')
        ->setIcon('fas fa-toggle-off')
        ->displayIf(static function ($entity) {
            return $entity->isEnabled();
        });

        $exportIndex = Action::new('export', 'Export prices to csv')
        ->setIcon('fa fa-download')
        ->linkToCrudAction('export')
        ->setCssClass('btn btn-primary')
        ->createAsGlobalAction();


        $actionDesactivateList = clone($actionDesactivate);
        return $actions
            ->disable(Action::DETAIL)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, $exportIndex)
           
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-pencil')->setLabel("Edit");
            })
            ->add(Crud::PAGE_INDEX, $actionDesactivateList)
            ->add(Crud::PAGE_INDEX, $actionReactivateList)
            ->addBatchAction(
                Action::new('desactivateBatch', 'Disable')
                ->linkToCrudAction('desactivateBatch')
                ->addCssClass('btn btn-primary')
                ->setIcon('fas fa-toggle-off')
            )
            ->addBatchAction(
                Action::new('activateBatch', 'Enable')
                ->linkToCrudAction('activateBatch')
                ->addCssClass('btn btn-primary')
                ->setIcon('fas fa-toggle-on')
            )
        ;
    }




    public function deactivate(AdminContext $context)
    {
        $product = $context->getEntity()->getInstance();
        $manager = $this->container->get('doctrine')->getManager();
       
        $product->addLog('Product disabled');
        $product->setEnabled(false);
        $manager->flush();
        $this->addFlash('success', 'Product has been disabled');
        return $this->redirect($context->getReferrer());
    }


    public function reactivate(AdminContext $context)
    {
        $product = $context->getEntity()->getInstance();
        $manager = $this->container->get('doctrine')->getManager();
        if($product->isActiveInBc() && $product->getBrand()->isEnabled()) {
            $product->addLog('Product enabled');
            $product->setEnabled(true);
            $manager->flush();
            $this->addFlash('success', 'Product has been enabled');
        } else {
            $this->addFlash('success', 'Product cannot be enabled');
        }
        
        return $this->redirect($context->getReferrer());
    }




    public function activateBatch(BatchActionDto $batchActionDto)
    {
    
        $entityManager = $this->container->get('doctrine')->getManagerForClass(Product::class);
        foreach ($batchActionDto->getEntityIds() as $id) {
            $product = $entityManager->find(Product::class, $id);
            if($product->isActiveInBc() && $product->getBrand()->isEnabled()) {
                $product->addLog('Product enabled');
                $product->setEnabled(true);
            }
        }
        $entityManager->flush();
       
        return $this->redirect($batchActionDto->getReferrerUrl());
    }



    public function desactivateBatch(BatchActionDto $batchActionDto)
    {
    
        $entityManager = $this->container->get('doctrine')->getManagerForClass(Product::class);
        foreach ($batchActionDto->getEntityIds() as $id) {
            $product = $entityManager->find(Product::class, $id);
            $product->addLog('Product disabled');
            $product->setEnabled(false);
        }
        $entityManager->flush();
       
        return $this->redirect($batchActionDto->getReferrerUrl());
    }


}
