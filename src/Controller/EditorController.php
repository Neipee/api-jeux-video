<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
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

class EditorController extends AbstractController
{
    #[Route('/api/v1/editors', name: 'editors', methods: ['GET'])]
    public function getEditors(EditorRepository $editorRepository, Request $request,
                               TagAwareCacheInterface $cachePool, SerializerInterface $serializer): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheIdentifier = "getAllEditors-" . $page . "-" . $limit;
        $jsonEditorList = $cachePool->get($cacheIdentifier,
            function (ItemInterface $item) use ($editorRepository, $page, $limit, $serializer) {
                $item->tag("editorsCache");
                $editorList = $editorRepository->findAllWithPagination($page, $limit);
                return $serializer->serialize($editorList, 'json', ['groups' => 'getEditors']);
            }
        );

        return new JsonResponse($jsonEditorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/editors/{id}',
        name: 'editor',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function getEditor(Editor $editor): JsonResponse {
        if ($editor->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        return $this->json($editor, Response::HTTP_OK, [], ['groups' => 'getEditors']);
    }

    #[Route('/api/v1/editors', name: "createEditor", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à créer un éditeur')]
    public function createEditor(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $editor = $serializer->deserialize($request->getContent(), Editor::class, 'json');

        $errors = $validator->validate($editor);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["editorsCache"]);
        $em->persist($editor);
        $em->flush();

        $location = $urlGenerator->generate(
            'editor',
            ['id' => $editor->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            $editor, Response::HTTP_CREATED,
            ["Location" => $location], ['groups' => 'getEditors']
        );
    }

    #[Route('/api/v1/editors/{id}', name: "updateEditor", methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à modifier un éditeur')]
    public function updateEditor(
        Request $request, SerializerInterface $serializer, Editor $currentEditor,
        EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($currentEditor->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $updatedEditor = $serializer->deserialize($request->getContent(),
            Editor::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEditor]);

        $errors = $validator->validate($updatedEditor);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["editorsCache"]);
        $em->persist($updatedEditor);
        $em->flush();

        $location = $urlGenerator->generate(
            'editor', ['id' => $updatedEditor->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(['status' => 'success'], Response::HTTP_OK, ["Location" => $location]);
    }

    #[Route('/api/v1/editors/{id}', name: 'deleteEditor', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à supprimer un éditeur')]
    public function deleteEditor(Editor $editor, EntityManagerInterface $em,
                                 TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($editor->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $cachePool->invalidateTags(["editorsCache"]);
        $editor->setDeletedAt(new \DateTime());
        $em->flush();

        return $this->json(['status' => 'success'], Response::HTTP_OK);
    }
}
