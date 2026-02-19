<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/v1/api/login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['login'], $data['pass'])) {
            return $this->json(['error' => 'Missing credentials'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['login' => $data['login']]);

        if (!$user || $user->getPass() !== ($data['pass'] ?? '')) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $tokenString = bin2hex(random_bytes(32));

        $token = new ApiToken();
        $token->setUser($user);
        $token->setToken($tokenString);
        $token->setExpiresAt((new \DateTime())->modify('+3 day'));

        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json([
            'token' => $tokenString,
            'expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s')
        ]);
    }
}
