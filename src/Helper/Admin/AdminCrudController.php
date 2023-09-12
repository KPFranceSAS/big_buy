<?php

namespace App\Helper\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use ReflectionClass;

abstract class AdminCrudController extends AbstractCrudController
{


    public function getName()
    {
        $reflectionClass = new ReflectionClass($this);
        return str_replace('CrudController', '', $reflectionClass->getShortName());
    }


    public function getDefautOrder(): array
    {
        return ['createdAt' => "DESC"];
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->getName())
            ->setEntityLabelInPlural($this->getName() . 's')
            ->setDateTimeFormat('dd-MM-yyyy HH:mm')
            ->setDefaultSort($this->getDefautOrder())
            ->setPaginatorPageSize(100)
            ->showEntityActionsInlined(true);
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add('index', Action::new(Action::DETAIL)->linkToCrudAction(Action::DETAIL)->setIcon('fa fa-eye'))
            ->disable(Action::EDIT)
            ->disable(Action::NEW)
            ->disable(Action::DELETE);
    }


}
