<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
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

class UserController extends AbstractController
{
    #[Route('/api/v1/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à voir les utilisateurs')]
    public function getUsers(UserRepository $userRepository, Request $request,
                             TagAwareCacheInterface $cachePool, SerializerInterface $serializer): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheIdentifier = "getAllUsers-" . $page . "-" . $limit;
        $jsonUserList = $cachePool->get($cacheIdentifier,
            function (ItemInterface $item) use ($userRepository, $page, $limit, $serializer) {
                $item->tag("usersCache");
                $userList = $userRepository->findAllWithPagination($page, $limit);
                return $serializer->serialize($userList, 'json', ['groups' => 'getUsers']);
            }
        );

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/users/{id}',
        name: 'user',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à voir cet utilisateur')]
    public function getOneUser(User $user): JsonResponse {
        if ($user->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'getUsers']);
    }

    #[Route('/api/v1/users', name: "createUser", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à créer un utilisateur')]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword(password_hash($user->getPassword(), PASSWORD_ARGON2ID));

        $cachePool->invalidateTags(["usersCache"]);
        $em->persist($user);
        $em->flush();

        $location = $urlGenerator->generate(
            'user',
            ['id' => $user->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(
            $user, Response::HTTP_CREATED,
            ["Location" => $location], ['groups' => 'getUsers']
        );
    }

    #[Route('/api/v1/users/{id}', name: "updateUser", requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à modifier un utilisateur')]
    public function updateUser(
        Request $request, SerializerInterface $serializer, User $currentUser,
        EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($currentUser->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $oldPassword = $currentUser->getPassword();

        $updatedUser = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

        $errors = $validator->validate($updatedUser);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($updatedUser->getPassword() !== $oldPassword) {
            $updatedUser->setPassword(password_hash($updatedUser->getPassword(), PASSWORD_ARGON2ID));
        }

        $cachePool->invalidateTags(["usersCache"]);
        $em->persist($updatedUser);
        $em->flush();

        $location = $urlGenerator->generate(
            'user', ['id' => $updatedUser->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(['status' => 'success'], Response::HTTP_OK, ["Location" => $location]);
    }

    #[Route('/api/v1/users/{id}', name: 'deleteUser', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'êtes pas autorisé à supprimer un utilisateur')]
    public function deleteUser(User $user, EntityManagerInterface $em,
                               TagAwareCacheInterface $cachePool): JsonResponse
    {
        if ($user->getDeletedAt() !== null) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $cachePool->invalidateTags(["usersCache"]);
        $user->setDeletedAt(new \DateTime());
        $em->flush();

        return $this->json(['status' => 'success'], Response::HTTP_OK);
    }
}
