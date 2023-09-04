<?php

namespace App\Controller\Admin;

use App\Entity\AccountRequest;
use App\Entity\Customer;
use App\Entity\ImportPricing;
use App\Entity\MagentoRequest;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\User;
use App\Entity\WebOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/views/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Big Buy');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Order', 'fas fa-shopping-cart', SaleOrder::class);
        yield MenuItem::linkToCrud('Product', 'fas fa-barcode', Product::class);
        yield MenuItem::linkToCrud('Import', 'fas fa-upload', ImportPricing::class);
        yield MenuItem::linkToCrud('User', 'fa fa-user', User::class);
    }
}
