<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\CategoryRepository;
use App\Repository\EditorRepository;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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

class VideoGameController extends AbstractController
{
    #[Route('/api/v1/videogames', name: 'videoGames', methods: ['GET'])]
    public function getVideoGames(VideoGameRepository $videoGameRepository, Request $request,
                                  TagAwareCacheInterface $cachePool, SerializerInterface $serializer): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheIdentifier = "getAllVideoGames-" . $page . "-" . $limit;
        $jsonVideoGameList = $cachePool->get($cacheIdentifier,
            function (ItemInterface $item) use ($videoGameRepository, $page, $limit, $serializer) {
                $item->tag("videoGamesCache");
                $videoGameList = $videoGameRepository->findAllWithPagination($page, $limit);
                return $serializer->serialize($videoGameList, 'json', ['groups' => 'getVideoGames']);
            }
        );

        return new JsonResponse($jsonVideoGameList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/videogames/{id}',
        name: 'videoGame',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getVideoGame(VideoGame $videoGame): JsonResponse {
        if ($videoGame->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        return $this->json($videoGame, Response::HTTP_OK, [], ['groups' => 'getVideoGames']);
    }

    #[Route('/api/v1/videogames', name: "createVideoGame", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à créer un jeu vidéo')]
    public function createVideoGame(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        CategoryRepository $categoryRepository,
        EditorRepository $editorRepository,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $videoGame = $serializer->deserialize($request->getContent(), VideoGame::class, 'json');

        $category = $categoryRepository->find($request->get('idCategory', 0));
        if ($category && $category->getDeletedAt() === null) {
            $videoGame->setCategory($category);
        }

        $editor = $editorRepository->find($request->get('idEditor', 0));
        if ($editor && $editor->getDeletedAt() === null) {
            $videoGame->setEditor($editor);
        }

        $errors = $validator->validate($videoGame);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["videoGamesCache"]);
        $em->persist($videoGame);
        $em->flush();

        $location = $urlGenerator->generate(
            'videoGame',
            ['id' => $videoGame->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            $videoGame, Response::HTTP_CREATED,
            ["Location" => $location], ['groups' => 'getVideoGames']
        );
    }

    #[Route('/api/v1/videogames/{id}', name: "updateVideoGame", requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à modifier un jeu vidéo')]
    public function updateVideoGame(
        Request $request, SerializerInterface $serializer, VideoGame $currentVideoGame,
        EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator, CategoryRepository $categoryRepository,
        EditorRepository $editorRepository, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($currentVideoGame->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $updatedVideoGame = $serializer->deserialize($request->getContent(),
            VideoGame::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentVideoGame]);

        $category = $categoryRepository->find($request->get('idCategory', 0));
        if ($category && $category->getDeletedAt() === null) {
            $updatedVideoGame->setCategory($category);
        }

        $editor = $editorRepository->find($request->get('idEditor', 0));
        if ($editor && $editor->getDeletedAt() === null) {
            $updatedVideoGame->setEditor($editor);
        }

        $errors = $validator->validate($updatedVideoGame);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["videoGamesCache"]);
        $em->persist($updatedVideoGame);
        $em->flush();

        $location = $urlGenerator->generate(
            'videoGame', ['id' => $updatedVideoGame->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(['status' => 'success'], Response::HTTP_OK, ["Location" => $location]);
    }

    #[Route('/api/v1/videogames/{id}', name: 'deleteVideoGame', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à supprimer un jeu vidéo')]
    public function deleteVideoGame(VideoGame $videoGame, EntityManagerInterface $em,
                                    TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($videoGame->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $cachePool->invalidateTags(["videoGamesCache"]);
        $videoGame->setDeletedAt(new \DateTime());
        $em->flush();

        return $this->json(['status' => 'success'], Response::HTTP_OK);
    }

    #[Route('/api/v1/videogames/{id}/cover', name: 'uploadCoverVideoGame', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à modifier la jaquette d\'un jeu vidéo')]
    public function uploadCoverVideoGame(VideoGame $videoGame, Request $request, EntityManagerInterface $em,
                                         TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($videoGame->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $coverImage = $request->files->get('coverImage');
        if (!$coverImage) {
            return new JsonResponse(['error' => 'Aucune image envoyée (champ coverImage)'], Response::HTTP_BAD_REQUEST);
        }

        $extension = $coverImage->guessExtension();
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return new JsonResponse(['error' => 'Le fichier doit être une image (jpg, png, gif ou webp)'], Response::HTTP_BAD_REQUEST);
        }

        $newFilename = uniqid().'.'.$extension;
        try {
            $coverImage->move(
                $this->getParameter('cover_directory'),
                $newFilename
            );
            $videoGame->setCoverImage($newFilename);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Impossible d\'enregistrer l\'image'], 500);
        }

        $cachePool->invalidateTags(["videoGamesCache"]);
        $em->flush();

        return $this->json($videoGame, Response::HTTP_OK, [], ['groups' => 'getVideoGames']);
    }
}
