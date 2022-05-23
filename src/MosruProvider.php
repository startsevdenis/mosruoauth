<?php

namespace StartsevDenis\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use Symfony\Component\DependencyInjection\ContainerInterface;


class MosruProvider extends AbstractProvider
{
    protected $authUrl = 'https://login-tech.mos.ru/sps/oauth/ae';
    protected $tokenUrl = 'https://login-tech.mos.ru/sps/oauth/te';
    protected $infoUrl = 'https://login-tech.mos.ru/sps/oauth/me';

    protected $environment = 'production';

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (isset($options['environment']) && $options['environment'] === 'production') {
            $this->authUrl = 'https://login.mos.ru/sps/oauth/ae';
            $this->tokenUrl = 'https://login.mos.ru/sps/oauth/te';
            $this->infoUrl = 'https://login.mos.ru/sps/oauth/me';
        }
    }

    /**
     * Валидация ответа
     *
     * @param ResponseInterface $response
     * @param $data
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error_code'])) {
            throw new IdentityProviderException($data['error_msg'], $data['error_code'], $response->getBody());
        } elseif (isset($data['error'])) {
            throw new IdentityProviderException($data['error'],
                $response->getStatusCode(), $response->getBody());
        }
    }

    /**
     * URL для авторизации
     * @param array $options
     * @return string
     */
    public function getAuthorizationUrl(array $options = [])
    {
        $base   = $this->getBaseAuthorizationUrl();
        $params = $this->getAuthorizationParameters($options);
        $query  = $this->getAuthorizationQuery($params);

        return $this->appendQuery($base, $query);
    }

    /**
     * Получение AccessToken
     * @param $grant
     * @param array $options
     * @return AccessToken|AccessTokenInterface
     * @throws IdentityProviderException
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => str_replace('http://', 'https://', $this->redirectUri),

        ];

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        if (false === is_array($response)) {
            throw new \UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Создание объекта владельца аккаунта с которым авторзовался пользователь
     * @param array $response
     * @param AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new MosruResourceOwner($response, $token);
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return string[]
     */
    protected function getDefaultScopes() :array
    {
        return ['openid profile contacts'];
    }

    /**
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->tokenUrl;
    }

    /**
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->authUrl;
    }

    /**
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->infoUrl;
    }

    /**
     * @return string
     */
    protected function getScopeSeparator() :string
    {
        return '+';
    }

    protected function getAuthorizationHeaders($token = null)
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    /**
     * Appends a query string to a URL.
     *
     * Очень часто админы ленятся прокидывать HTTPS в PHP поэтому приходится принудительно менять урлы
     * Без https мы больше все равно не работаем
     *
     * @param  string $url The URL to append the query to
     * @param  string $query The HTTP query string
     * @return string The resulting URL
     */
    protected function appendQuery($url, $query)
    {
        $url = parent::appendQuery($url, $query);
        return str_replace('http://', 'https://', str_replace('%3A%2F%2F', '://', str_replace('%2B', '+', $url)));
    }
}