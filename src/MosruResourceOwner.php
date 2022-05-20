<?php

namespace StartsevDenis\OAuth2\Client\Provider;


use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class MosruResourceOwner implements ResourceOwnerInterface
{
    /**
     * Response с информацией о пользователе
     *
     * @var array
     */
    private $response;

    /**
     * Данные токена авторизации
     * @var AccessToken
     */
    private $token;

    private $tokenData = [];

    function __construct($response, $token)
    {
        $this->response = $response;
        $this->token = $token;
    }

    /**
     * Извлечение информации из id_token
     * @return array
     */
    private function parseTokenData() :array
    {
        if ($this->tokenData) {
            return $this->tokenData;
        }

        $r = [];

        $values = $this->token->getValues();
        if (isset($values['id_token']))
        {
            $parts = explode('.', $values['id_token']);
            if (isset($parts[1]))
            {
                $decoded = base64_decode(strtr($parts[1],'-_', '+/'));

                if ($decoded)
                {
                    $je = json_decode($decoded, true);
                    if ($je)
                    {
                        $r = $je;
                    }
                }

            }
        }

        $this->tokenData = $r;

        return $r;
    }

    /**
     * Получение id_token
     * @return string|null
     */
    public function getTokenId() :?string
    {
        $values = $this->token->getValues();
        return $values['id_token'] ?? null;
    }

    /**
     * Проверка является пользователь организацией (ЮЛ, ИП и т.д.)
     * @return bool
     */
    public function isLegalPerson() :bool
    {
        if ($this->getCompanyInn())
        {
            return true;
        }
        return false;
    }

    /**
     * ИНН организации
     * @return string|null
     */
    public function getCompanyInn() :?string
    {
        $data = $this->parseTokenData();

        if (isset($this->parseTokenData()['org_INN']))
        {
            $m = [];
            preg_match("/(?<inn>(\d).*)/ui", $data['org_INN'], $m);

            $tInn = isset($m['inn']) ? $m['inn'] : $data['org_INN'];
            if (mb_strlen($tInn) == 12 && mb_substr($tInn, 0, 2) === '00')
            {
                return mb_substr($tInn, 2, 10);
            }
            else
            {
                return $tInn;
            }
        }
        return null;
    }

    /**
     * Идентификатор сертификата с которым авторизовался пользователь
     * Может отсутствовать если он не использовался при авторизации
     * @return string|null
     */
    public function getCertificateId() :?string
    {
        $data = $this->parseTokenData();
        if (isset($data['certificateID']))
        {
            return $data['certificateID'];
        }

        return null;
    }

    /**
     * ОГРН организации
     * @return string|null
     */
    public function getCompanyOgrn() :?string
    {
        $data = $this->parseTokenData();
        if (isset($data['org_OGRN']))
        {
            return $data['org_OGRN'];
        }
        return null;
    }

    /**
     * Метод авторизации в внутри СУДИР
     * @return string|null
     */
    public function getAuthMethod() :?string
    {
        $data = $this->parseTokenData();
        if (isset($data['amr']) && is_array($data['amr']))
        {
            return implode('_', $data['amr']);
        }
        return null;
    }

    /**
     * Название организации
     * @return string|null
     */
    public function getCompanyName() :?string
    {
        $data = $this->parseTokenData();
        if (isset($data['org_name']))
        {
            return $data['org_name'];
        }
        return null;
    }

    /**
     * GUID пользователя СУДИР
     * @return string
     */
    public function getId() :string
    {
        return $this->response['guid'];
    }

    /**
     * Имя пользователя СУДИР
     * @return string
     */
    public function getFirstName() :string
    {
        if (isset($this->response['FirstName']) && trim($this->response['FirstName']))
        {
            return mb_ucfirst(trim($this->response['FirstName']));
        }
        return '';
    }

    /**
     * Фамилия пользователя СУДИР
     * @return string
     */
    public function getLastName() :string
    {
        if (isset($this->response['LastName']) && trim($this->response['LastName']))
        {
            return mb_ucfirst(trim($this->response['LastName']));
        }
        return '';
    }

    /**
     * Отчество пользователя СУДИР
     * @return string
     */
    public function getMiddleName() :string
    {
        if (isset($this->response['MiddleName']) && trim($this->response['MiddleName']))
        {
            return mb_ucfirst(trim($this->response['MiddleName']));
        }

        return '';
    }

    /**
     * Мобильный телефон пользователя СУДИР
     * @return string|null
     */
    public function getMobile() :?string
    {
        return $this->response['mobile'] ?? null;
    }

    /**
     * Email пользователя СУДИР
     * @return string|null
     */
    public function getMail() :?string
    {
        return $this->response['mail'] ?? null;
    }

    /**
     * Получение AccessToken
     * @return mixed|string
     */
    public function getAccessToken()
    {
        return $this->token->getToken();
    }

    /**
     * RefreshToken
     * @return mixed|string|null
     */
    public function getRefreshToken()
    {
        return $this->token->getRefreshToken();
    }

    /**
     * ExpiresTime
     * @return float|int|mixed|string|null
     */
    public function getExpiresTime()
    {
        return $this->token->getExpires();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response + ['accessToken' => $this->token->getToken(), 'refreshToken' => $this->token->getRefreshToken(), 'expires' => $this->token->getExpires()];
    }


}