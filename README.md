# Mos.ru OAuth2 client provider

[//]: # ([![Build Status]&#40;https://img.shields.io/travis/rakeev/oauth2-mailru.svg&#41;]&#40;https://travis-ci.org/rakeev/oauth2-mailru&#41;)
[![Latest Version](https://img.shields.io/packagist/v/aego/oauth2-mailru.svg)](https://packagist.org/packages/aego/oauth2-mailru)
[![License](https://img.shields.io/packagist/l/aego/oauth2-mailru.svg)](https://packagist.org/packages/aego/oauth2-mailru)

Данный пакет предоставляет интеграцию [Mos.ru](https://login.mos.ru) для [OAuth2 Client](https://github.com/thephpleague/oauth2-client).

## Установка

```sh
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

class AuthConroller extends AbstractController
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
