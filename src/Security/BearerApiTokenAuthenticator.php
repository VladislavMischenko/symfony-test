<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ApiToken;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class BearerApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $tokenValue = str_replace('Bearer ', '', $request->headers->get('Authorization'));

        $apiToken = $this->entityManager->getRepository(ApiToken::class)->findOneBy(['token' => $tokenValue]);

        if (!$apiToken || $apiToken->getExpiresAt() < new DateTime()) {
            throw new Exception('Invalid or expired token');
        }

        return new SelfValidatingPassport(
            new UserBadge($apiToken->getUser()->getUserIdentifier(), fn() => $apiToken->getUser())
        );
    }

    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?JsonResponse
    {
        return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?JsonResponse
    {
        return null;
    }
}
