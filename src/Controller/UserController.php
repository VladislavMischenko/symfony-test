<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1/api/users')]
final class UserController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ROOT')]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->json($users, 200, [], ['groups' => ['user:list']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(
        int $id,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse
    {
        $currentUser = $security->getUser();

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$this->checkUserAccess($currentUser, $user->getId())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        return $this->json($user, 200, [], ['groups' => ['user:detail']]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $role = $entityManager->getRepository(Role::class)->findOneBy(['name' => 'ROLE_USER']);

        if (!$role) {
            return $this->json(['error' => 'Role not found'], 404);
        }

        $user = new User();
        $user->setLogin($data['login'] ?? null);
        $user->setPhone($data['phone'] ?? null);
        $user->setPass($data['pass'] ?? null);
        $user->setRole($role);

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($user, 201, [], ['groups' => ['user:list']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        Security $security
    ): JsonResponse {
        $currentUser = $security->getUser();

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        if (!$this->checkUserAccess($currentUser, $user->getId())) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $user->setLogin($data['login'] ?? $user->getLogin());
        $user->setPhone($data['phone'] ?? $user->getPhone());
        $user->setPass($data['pass'] ?? $user->getPass());

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $entityManager->flush();

        return $this->json($user, 200, [], ['groups' => ['user:id']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ROOT')]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(true);
    }

    private function checkUserAccess(User $currentUser, int $requestUserId): bool
    {
        if ($currentUser->getRole()->getName() !== 'ROLE_ROOT' && $currentUser->getId() !== $requestUserId) {
            return false;
        }

        return true;
    }
}
