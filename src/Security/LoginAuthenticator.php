<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password)
        );
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->router->generate('Security_Login');
    }


    public function onAuthenticationSuccess(
        Request $request,
        \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token,
        string $firewallName
    ): Response {
        $user = $token->getUser();

        if ($user->isTwoFactorEnabled()) {
            $request->getSession()->set('2fa_required', true);

            return new RedirectResponse(
                $this->router->generate('app_2fa')
            );
        }

        return new RedirectResponse(
            $this->router->generate('Index_Index')
        );
    }
}