<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    // Cache local pour éviter les calculs répétés
    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email     = $request->request->get('email', '');
        $password  = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');

        // Optimisation: stockage minimal en session
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Création optimisée du Passport
        return new Passport(
            new UserBadge((string) $email, null),
            new PasswordCredentials((string) $password),
            [new CsrfTokenBadge('authenticate', $csrfToken ? (string) $csrfToken : null)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Optimisation: vérification du cache local d'abord
        $cacheKey = 'target_path_' . $firewallName;
        if (isset($this->cache[$cacheKey])) {
            return new RedirectResponse($this->cache[$cacheKey]);
        }

        // Vérification du chemin cible
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if ($targetPath) {
            $this->cache[$cacheKey] = $targetPath;

            return new RedirectResponse($targetPath);
        }

        // Redirection par défaut avec réponse mise en cache
        $dashboardUrl           = $this->urlGenerator->generate('app_dashboard');
        $this->cache[$cacheKey] = $dashboardUrl;

        return new RedirectResponse($dashboardUrl);
    }

    protected function getLoginUrl(Request $request): string
    {
        // Optimisation: éviter de recalculer l'URL à chaque fois
        if (!isset($this->cache['login_url'])) {
            $this->cache['login_url'] = $this->urlGenerator->generate(self::LOGIN_ROUTE);
        }

        return $this->cache['login_url'];
    }
}
