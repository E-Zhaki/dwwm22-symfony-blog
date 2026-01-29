<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Récupérons l'utilisateur connecté
        $user = $token->getUser();

        /**
         * Liste des rôles disponibles pour les utilisateur de l'application.
         *
         * @see
         *  - ROLE_SUPER_ADMIN
         *  - ROLE_ADMIN
         *  - ROLE_USER
         *
         * NB:
         *  - Le super admin possède tous les rôles.
         *  - L'admin possède aussi le rôle ROLE_USER
         */

        // Récupérons ses rôles
        $rolesUser = $user->getRoles();

        // Si l'utilisateur possède ROLE_ADMIN parmis ses rôles,
        if (in_array('ROLE_ADMIN', $rolesUser)) { // alors
            // Redirigeons-le vers la route menant à l'espace d'administration.
            return new RedirectResponse($this->urlGenerator->generate('app_admin_home'));
        }

        // Dans le cas contraire, ce qui veut dire que c'est un simple utilisateur (ROLE_USER),
        // Redirigeons-le vers la route menant à la page d'accueil.
        return new RedirectResponse($this->urlGenerator->generate('app_visitor_welcome'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
