<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CategoryController extends AbstractController
{
    #[Route('/api/v1/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository, Request $request,
                                  TagAwareCacheInterface $cachePool, SerializerInterface $serializer): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheIdentifier = "getAllCategories-" . $page . "-" . $limit;
        $jsonCategoryList = $cachePool->get($cacheIdentifier,
            function (ItemInterface $item) use ($categoryRepository, $page, $limit, $serializer) {
                $item->tag("categoriesCache");
                $categoryList = $categoryRepository->findAllWithPagination($page, $limit);
                return $serializer->serialize($categoryList, 'json', ['groups' => 'getCategories']);
            }
        );

        return new JsonResponse($jsonCategoryList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/categories/{id}',
        name: 'category',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getCategory(Category $category): JsonResponse {
        if ($category->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        return $this->json($category, Response::HTTP_OK, [], ['groups' => 'getCategories']);
    }

    #[Route('/api/v1/categories', name: "createCategory", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à créer une catégorie')]
    public function createCategory(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');

        $errors = $validator->validate($category);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["categoriesCache"]);
        $em->persist($category);
        $em->flush();

        $location = $urlGenerator->generate(
            'category',
            ['id' => $category->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            $category, Response::HTTP_CREATED,
            ["Location" => $location], ['groups' => 'getCategories']
        );
    }

    #[Route('/api/v1/categories/{id}', name: "updateCategory", requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à modifier une catégorie')]
    public function updateCategory(
        Request $request, SerializerInterface $serializer, Category $currentCategory,
        EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($currentCategory->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $updatedCategory = $serializer->deserialize($request->getContent(),
            Category::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCategory]);

        $errors = $validator->validate($updatedCategory);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["categoriesCache", "videoGamesCache"]);
        $em->persist($updatedCategory);
        $em->flush();

        $location = $urlGenerator->generate(
            'category', ['id' => $updatedCategory->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(['status' => 'success'], Response::HTTP_OK, ["Location" => $location]);
    }

    #[Route('/api/v1/categories/{id}', name: 'deleteCategory', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à supprimer une catégorie')]
    public function deleteCategory(Category $category, EntityManagerInterface $em,
                                   TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($category->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $cachePool->invalidateTags(["categoriesCache", "videoGamesCache"]);
        $category->setDeletedAt(new \DateTime());
        $em->flush();

        return $this->json(['status' => 'success'], Response::HTTP_OK);
    }
}
