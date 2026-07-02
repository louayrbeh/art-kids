<?php

namespace App\Controller\BackOffice;

use App\Entity\Category;
use App\Form\BackOffice\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ImageUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories', name: 'app_back_category_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('back_office/category/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageUploaderService $imageUploaderService,
    ): Response {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if (null !== $imageFile) {
                try {
                    $category->setImage($imageUploaderService->uploadCategoryImage($imageFile));
                } catch (\RuntimeException $exception) {
                    $this->addFlash('error', $exception->getMessage());

                    return $this->render('back_office/category/new.html.twig', [
                        'form' => $form->createView(),
                        'category' => $category,
                    ]);
                }
            }

            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'Categorie creee avec succes.');

            return $this->redirectToRoute('app_back_category_index');
        }

        return $this->render('back_office/category/new.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('back_office/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        ImageUploaderService $imageUploaderService,
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldImage = $category->getImage();
            $imageFile = $form->get('imageFile')->getData();

            if (null !== $imageFile) {
                try {
                    $newFilename = $imageUploaderService->uploadCategoryImage($imageFile);
                } catch (\RuntimeException $exception) {
                    $this->addFlash('error', $exception->getMessage());

                    return $this->render('back_office/category/edit.html.twig', [
                        'form' => $form->createView(),
                        'category' => $category,
                    ]);
                }

                $category->setImage($newFilename);
            }

            $entityManager->flush();

            if (null !== $imageFile) {
                $imageUploaderService->deleteCategoryImage($oldImage);
            }

            $this->addFlash('success', 'Categorie modifiee avec succes.');

            return $this->redirectToRoute('app_back_category_index');
        }

        return $this->render('back_office/category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        ImageUploaderService $imageUploaderService,
    ): Response {
        if ($this->isCsrfTokenValid('delete_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            if ($categoryRepository->hasActivities($category)) {
                $this->addFlash('error', 'Impossible de supprimer une categorie qui contient encore des activites.');

                return $this->redirectToRoute('app_back_category_index');
            }

            $imageFilename = $category->getImage();
            $entityManager->remove($category);
            $entityManager->flush();
            $imageUploaderService->deleteCategoryImage($imageFilename);
            $this->addFlash('success', 'Categorie supprimee avec succes.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_back_category_index');
    }
}
