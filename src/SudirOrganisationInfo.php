<?php

namespace StartsevDenis\OAuth2\Client\Provider;

class SudirOrganisationInfo
{
    protected $id;
    protected $name;
    protected $ogrn;
    protected $inn;

    /**
     * @param $id
     * @param $name
     * @param $ogrn
     * @param $inn
     */
    public function __construct($sudirData)
    {
        $this->id = $sudirData['id'];
        $this->name = $sudirData['NAME'];
        $this->ogrn = $sudirData['OGRN'];
        $this->inn = $sudirData['INN'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOgrn()
    {
        return $this->ogrn;
    }

    /**
     * @return string
     */
    public function getInn()
    {
        $m = [];
        preg_match("/(?<inn>(\d).*)/ui", $this->inn, $m);

        $tInn = $m['inn'] ?? $this->inn;
        if (mb_strlen($tInn) == 12 && mb_substr($tInn, 0, 2) === '00')
        {
            return mb_substr($tInn, 2, 10);
        }
        else
        {
            return $tInn;
        }
    }


}