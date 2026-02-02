<?php

namespace App\Controller\Admin\Category;

use App\Entity\Category;
use App\Form\Admin\CategoryFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class CategoryController extends AbstractController
{
    #[Route('/category/index', name: 'app_admin_category_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/admin/category/index.html.twig');
    }

    #[Route('/category/create', name: 'app_admin_category_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dd('Continuer la partie');
        }

        return $this->render('pages/admin/category/create.html.twig', [
            'categoryForm' => $form->createView(),
        ]);
    }
}
