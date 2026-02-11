<?php

namespace App\Controller\Admin\Tag;

use App\Entity\Tag;
use App\Form\Admin\TagFormType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class TagController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
    ) {
    }

    #[Route('/tag/list', name: 'app_admin_tag_index', methods: ['GET'])]
    public function index(): Response
    {
        $tags = $this->tagRepository->findAll();

        return $this->render('pages/admin/tag/index.html.twig', [
            'tags' => $tags,
        ]);
    }

    #[Route('/tag/create', name: 'app_admin_tag_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tag->setCreatedAt(new \DateTimeImmutable());
            $tag->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($tag);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le tag a été ajouté avec succès.');

            return $this->redirectToRoute('app_admin_tag_index');
        }

        return $this->render('pages/admin/tag/create.html.twig', [
            'tagForm' => $form->createView(),
        ]);
    }
}
