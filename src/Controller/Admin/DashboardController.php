<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use App\Entity\ImportPricing;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
use App\Entity\User;
use App\Report\ReportCreator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        return $this->redirectToRoute('dashboard');
    }



    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(ManagerRegistry $managerRegistry, Request $request, ReportCreator $reportCreator): Response
    {
        $dateReport = DateTime::createFromFormat('Y-m-d', $request->get('dateReport', date('Y-m-d')));
        $reportDto=$reportCreator->createReport($dateReport);

        $saleOrder = $managerRegistry->getManager()->getRepository(SaleOrder::class)->findOneByStatus(['status'=>SaleOrder::STATUS_OPEN]);
        return $this->render('admin/views/dashboard.html.twig', ['saleOrder'=>$saleOrder, 'reportDto' =>  $reportDto]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Big Buy');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('build/app.css')->addWebpackEncoreEntry('app');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Brand', 'far fa-copyright', Brand::class);
        yield MenuItem::linkToCrud('Product', 'fas fa-barcode', Product::class);
        yield MenuItem::linkToCrud('Import', 'fas fa-upload', ImportPricing::class);
        yield MenuItem::linkToCrud('Order', 'fas fa-shopping-cart', SaleOrder::class);
        yield MenuItem::linkToCrud('Order lines', 'fas fa-shopping-cart', SaleOrderLine::class);
        yield MenuItem::linkToCrud('User', 'fa fa-user', User::class);
    }
}
