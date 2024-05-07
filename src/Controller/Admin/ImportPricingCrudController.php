<?php

namespace App\Controller\Admin;

use App\Entity\ImportPricing;
use App\Entity\Product;
use App\Form\ConfirmImportPricingFormType;
use App\Form\ImportPricingFormType;
use App\Helper\Admin\AdminCrudController;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use function Symfony\Component\String\u;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportPricingCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportPricing::class;
    }


    public function getDefautOrder(): array
    {
        return ['id' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('statusLitteral', 'Status')->hideOnForm()->setTemplatePath('admin/fields/importPricing/jobStatus.html.twig'),
            TextField::new('username')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
            ArrayField::new('content')->setTemplatePath('admin/fields/importPricing/contentJob.html.twig')->onlyOnDetail(),
            ArrayField::new('getWarningErrorLogs', 'Warnings and errors')->setTemplatePath('admin/fields/importPricing/logs.html.twig')->onlyOnDetail(),
            ArrayField::new('logs')->setTemplatePath('admin/fields/importPricing/logs.html.twig')->onlyOnDetail(),
        ];
    }




    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(CRUD::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->disable(Action::SAVE_AND_ADD_ANOTHER)
                ->add(
                    Crud::PAGE_INDEX,
                    Action::new('import', 'Import')
                        ->setIcon('fa fa-plus')
                        ->createAsGlobalAction()
                        ->linkToCrudAction('import')
                )
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel("Show");
            });
    }



    public function createCsvSampleFile(Request $request)
    {
        $typeFile = $request->get('typeFile', 'csv');
       
        if ($typeFile=='csv') {
            $writer = WriterEntityFactory::createCSVWriter();
            $writer->setFieldDelimiter(';');
        } else {
            $writer = WriterEntityFactory::createXLSXWriter();
        }

        $lines = [
                ['Sku', 'Price', 'Force Price'],
                ['XXX-SKU', '125', ''],
                ['XXX-SKU-2', '135,25', ''],
                ['XXX-SKU-5', '135,25', '1']
        ];

        
        $fileName = u('export prices'.' ' . date('Ymd His'))->snake() . '.'.$typeFile;

        $h = fopen('php://output', 'r');
        $writer->openToBrowser($fileName);
        foreach($lines as $line) {
            $singleRow = WriterEntityFactory::createRowFromArray($line);
            $writer->addRow($singleRow);
        }
        
        $writer->close();
        return new Response(stream_get_contents($h));
    }
    





    public function import(AdminContext $context)
    {
        $import = new ImportPricing();
        $form = $this->createForm(ImportPricingFormType::class, $import);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $import->setUser($this->getUser());
            $import->setStatus(ImportPricing::Status_Created);
            $datas = $this->importDatas($import->uploadedFile);
            
            $import->setContent($datas);
            $manager =  $this->container->get('doctrine')->getManager();
            $manager->persist($import);
            
            $manager->flush();
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction("confirm")
                ->setEntityId($import->getId())
                ->generateUrl();
            return $this->redirect($url);
        }
        return $this->renderForm('admin/crud/importPricing/import.html.twig', ['form' => $form, 'import' => $import]);

       
    }



   


    public function confirm(AdminContext $context, ValidatorInterface $validator)
    {
        $import = $context->getEntity()->getInstance();
        if ($import->getStatus() != ImportPricing::Status_Created) {
            $this->createAccessDeniedException('Import already confirmed');
        }
        $form = $this->createForm(ConfirmImportPricingFormType::class, $import);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            /**@var App\Entity\User */
            $user = $this->getUser();

            /**@var Symfony\Component\Form\ClickableInterface */
            $btnToImport = $form->get('toImport');
            if ($btnToImport->isClicked()) {
                $nextAction = ImportPricing::Status_ToImport;
                $import->addLog('Content confirmed by ' . $user->getUserIdentifier());
                $this->importPrices($import, $validator);

                $this->addFlash('success', 'Your import has been done.');

                return $this->redirect(
                    $this->container
                        ->get(AdminUrlGenerator::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($import->getId())
                        ->generateUrl()
                );
            } else {
                $nextAction = ImportPricing::Status_Cancelled;
                $import->addLog('Job cancelled by ' . $user->getUserIdentifier());
                $this->addFlash('success', 'You cancelled your job.');
                $import->setStatus($nextAction);
                $this->container->get('doctrine')->getManager()->flush();
                return $this->redirect(
                    $this->container
                        ->get(AdminUrlGenerator::class)
                        ->setAction(Action::INDEX)
                        ->setEntityId(null)
                        ->generateUrl()
                );
            }
           
        }
        return $this->renderForm('admin/crud/importPricing/confirm.html.twig', ['form' => $form, 'import' => $import]);
    }



    public function addLog(ImportPricing $importPricing, $log)
    {
        $importPricing->addLog($log);
    }


    public function addWarning(ImportPricing $importPricing, $log)
    {
        $importPricing->addWarning($log);
    }

    public function addSuccess(ImportPricing $importPricing, $log)
    {
        $importPricing->addSuccess($log);
    }

    public function addError(ImportPricing $importPricing, $log)
    {
        $importPricing->addError($log);
    }


    private function importPrices(ImportPricing $importPricing, ValidatorInterface $validator)
    {
        $i = 2;
        $created = 0;
        $notCreated = 0;
        $contentLines = $importPricing->getContent();
        foreach ($contentLines as $contentLine) {
            $this->addLog($importPricing, '######################################################');
            $this->addLog($importPricing, 'Processing line ' . $i);
            $importLineOk = $this->importLinePricing($importPricing, $contentLine, $i, $validator);
          

            if ($importLineOk) {
                $created++;
                $this->addSuccess($importPricing, 'Items updated on line ' . $i);
            } else {
                $notCreated++;
                $this->addError($importPricing, 'Items skipped on line ' . $i);
            }
            $this->container->get('doctrine')->getManager()->flush();
            $i++;
        }
        $this->addLog($importPricing, '-------------------');
        $this->addLog($importPricing, "Result import on :" . count($contentLines) . " lines");
        $this->addLog($importPricing, "$created lines succeeded");
        $this->addLog($importPricing, "$notCreated lines skipped");
        $importPricing->setStatus(ImportPricing::Status_Imported);
        $this->container->get('doctrine')->getManager()->flush();
    }


    private function importLinePricing(ImportPricing $importPricing, array $line, int $lineNumber, ValidatorInterface $validator)
    {
        if (!array_key_exists('Sku', $line)) {
            $this->addError($importPricing, 'Column Sku is required');
            return false;
        }

        if (!array_key_exists('Price', $line)) {
            $this->addError($importPricing, 'Column price is required');
            return false;
        }

        /** @var Product  */
        $productDb =  $this->container->get('doctrine')->getManager()->getRepository(Product::class)->findOneBySku(trim($line["Sku"]));
        if (!$productDb) {
            $this->addError($importPricing, 'No product with sku ' . $line["Sku"]. ' on line '.$lineNumber);
            return false;
        } else {
            $this->addLog($importPricing, 'Find product with' . $line["Sku"]. ' on line '.$lineNumber);
        }


        $priceFormatted = floatval(str_replace(',', '.', $line["Price"]));

        if($priceFormatted<=0) {
            $this->addError($importPricing, 'Price '.$priceFormatted .'is not correct on  line '.$lineNumber);
            return false;
        }

        if (array_key_exists('Force Price', $line) && (int)$line['Force Price'] == 1) {
            $productDb->setForcePrice(true);
        } else {
            $productDb->setForcePrice(false);
        }



        $productDb->setPrice(floatval(str_replace(',', '.', $line["Price"])));

        $errors = $validator->validate($productDb);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addError($importPricing, 'The product '.$line["Sku"].' has some issues on ' . $error->getPropertyPath() . ' > ' . $error->getMessage() . ' on line ' . $lineNumber);
            }
            $this->container->get('doctrine')->getManager()->detach($productDb);
            return false;
        }
        return true;
    }




    private function importDatas(UploadedFile $uploadedFile)
    {
        $header = null;
        $datas = [];

        if (substr($uploadedFile->getClientOriginalName(), -3) == 'csv') {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(';');
            $reader->setFieldEnclosure('"');
            $isExcel= false;
        } else {
            $reader = ReaderEntityFactory::createReaderFromFile($uploadedFile->getClientOriginalName());
            $isExcel= true;
        }

        $reader->open($uploadedFile->getPathname());

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if (!$header) {
                    foreach ($row->getCells() as $cell) {
                        $header[] = $cell->getValue();
                    }
                } else {
                    $cells = $row->toArray();
                    if (count($cells) == count($header)) {
                        $dataLines = array_combine($header, $cells);
                        $datas[] = $dataLines;
                    } elseif($isExcel && count($cells) < count($header)) {
                        while(count($cells) != count($header)) {
                            $cells[]=null;
                        }
                        $dataLines = array_combine($header, $cells);
                        $datas[] = $dataLines;
                    }
                }
            }
            return $datas;
        }
    }
}
