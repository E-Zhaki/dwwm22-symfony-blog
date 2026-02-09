<?php

namespace App\Controller\Admin\Post;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class PostController extends AbstractController
{
    #[Route('/post/index', name: 'app_admin_post_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/admin/post/index.html.twig');
    }

    #[Route('/post/create', name: 'app_admin_post_create', methods: ['GET'])]
    public function create(): Response
    {
        $post = new Post();

        // $this->createForm();

        return $this->render('pages/admin/post/create.html.twig');
    }
}
