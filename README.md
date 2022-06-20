# Mos.ru OAuth2 client provider

[//]: # ([![Build Status]&#40;https://img.shields.io/travis/rakeev/oauth2-mailru.svg&#41;]&#40;https://travis-ci.org/rakeev/oauth2-mailru&#41;)
[![Latest Version](https://img.shields.io/packagist/v/aego/oauth2-mailru.svg)](https://packagist.org/packages/aego/oauth2-mailru)
[![License](https://img.shields.io/packagist/l/aego/oauth2-mailru.svg)](https://packagist.org/packages/aego/oauth2-mailru)

Данный пакет предоставляет интеграцию [Mos.ru](https://login.mos.ru) для [OAuth2 Client](https://github.com/thephpleague/oauth2-client).

## Установка

```sh
composer require knpuniversity/oauth2-client-bundle
composer require startsevdenis/oauth2-mosru
```

## Использоваие

### 1. Настройка клиента

```yaml
#knpu_oauth2_client.yaml

knpu_oauth2_client:
  clients:
    mosru:
      type: generic
      provider_class: StartsevDenis\OAuth2\Client\Provider\MosruProvider
      provider_options:
        environment: '%env(MOSRU_APP_ENV)%' #production or test
        scope: [ 'openid', 'profile', 'contacts', 'usr_grps' ] #необязательный параметр scope по умолчанию 'openid', 'profile', 'contacts'
      client_id: '%env(MOSRU_APP_ID)%'
      client_secret: '%env(MOSRU_APP_SECRET)%'
      redirect_route: mosru_check
```

### 2. Два роута в контроллере

```php
# AuthConroller

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
...
    /**
     * Auth start
     *
     * @Route("/connect/mosru", name="connect_mosru_start")
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('mosru')
            ->redirect();
    }
    
    /**
     * Auth end  
     * @Route("/auth/mosru/check", name="mosru_check")
     */
    public function mosruCheck()
    {
        #logic
    }
}
``` 

### 3. Авторизатор

В зависимости от версии symfony используем соответствующий авторизатор. 
Примеры авторизаторв на странице [KnpUOAuth2ClientBundle](https://github.com/knpuniversity/oauth2-client-bundle#authenticating-with-the-new-symfony-authenticator)

Пример авторизатора Symfony 5.4

```yaml
#security.yaml

security:
  enable_authenticator_manager: true
  ...
  firewalls:
    ...
    main:
      ...
      custom_authenticators:
        - App\Security\MosRuAuthenticator


```

```php
# App\Security\MosRuAuthenticator

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class MosRuAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'mosru_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('mosru');
        $accessToken = $this->fetchAccessToken($client);
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /* @var \StartsevDenis\OAuth2\Client\Provider\MosruResourceOwner */
                $owner = $client->fetchUserFromToken($accessToken);

                #getUser logic

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        //return url logic

        $targetUrl = $this->router->generate('index_page');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}

```