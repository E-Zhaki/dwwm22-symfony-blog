<?php

namespace App\Controller\Admin\Post;

use App\Entity\Post;
use App\Entity\User;
use App\Form\Admin\PostFormType;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class PostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PostRepository $postRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    #[Route('/post/index', name: 'app_admin_post_index', methods: ['GET'])]
    public function index(): Response
    {
        $posts = $this->postRepository->findAll();

        return $this->render('pages/admin/post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/create', name: 'app_admin_post_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if (0 == $this->categoryRepository->count()) {
            $this->addFlash('warning', 'Pour rédiger des articles, vous devez avoir une une catégorie.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        $post = new Post();

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User */
            $admin = $this->getUser();
            $post->setUser($admin);

            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', "L'article a été ajouté avec succès.");

            return $this->redirectToRoute('app_admin_post_index');
        }

        return $this->render('pages/admin/post/create.html.twig', [
            'postForm' => $form->createView(),
        ]);
    }

    #[Route('/post/{id<\d+>}/edit', name: 'app_admin_post_edit', methods: ['GET', 'POST'])]
    public function edit(Post $post, Request $request): Response
    {
        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User */
            $admin = $this->getUser();
            $post->setUser($admin);

            $post->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', "L'article a été modifié avec succès.");

            return $this->redirectToRoute('app_admin_post_index');
        }

        return $this->render('pages/admin/post/edit.html.twig', [
            'postForm' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/post/{id<\d+>}/delete', name: 'app_admin_post_delete', methods: ['POST'])]
    public function delete(Post $post, Request $request): Response
    {
        if ($this->isCsrfTokenValid("delete-post-{$post->getId()}", $request->request->get('csrf_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();

            $this->addFlash('success', "L'article a été supprimé");
        }

        return $this->redirectToRoute('app_admin_post_index');
    }

    #[Route('/post/{id<\d+>}/show', name: 'app_admin_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('pages/admin/post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id<\d+>}/publish', name: 'app_admin_post_publish', methods: ['POST'])]
    public function publish(Post $post, Request $request): Response
    {
        // 1. Si le jéton de sécurité est invalide, alors
        if (!$this->isCsrfTokenValid("publish-post-{$post->getId()}", $request->request->get('csrf_token'))) {
            // Redirigeons l'administrateur vers la page listant les articles.
            // Puis, arrêter l'exécution du script.
            return $this->redirectToRoute('app_admin_post_index');
        }

        /*
         * Dans le cas contraire,
         * Procédons à la publication ou au retrait de publication de l'article.
         */

        // 2. Si l'article n'est pas encore publié, alors
        if (!$post->isPublished()) {
            // Publions-le
            $post->setIsPublished(true);

            // Mettons à jour la date de publication
            $post->setPublishedAt(new \DateTimeImmutable());

            // Générons le message flash correspondant.
            $this->addFlash('success', "L'article a été publié");
        } else {
            // 3. Dans le cas contraire, retirer l'article de la liste des publications,
            $post->setIsPublished(false);

            // Mettons à jour la date de publication
            $post->setPublishedAt(null);

            // Générons le message flash correspondant.
            $this->addFlash('success', "L'article a été retiré de la liste des publications.");
        }

        // Mettre à jour les informations en base de données
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Effectuer une redirection vers la page listant les articles
        // Puis, arrêter l'exécution du script.
        return $this->redirectToRoute('app_admin_post_index');
    }
}
