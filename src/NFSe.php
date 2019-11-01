<?php
namespace Umobi\NFSe;

use NFePHP\Common\Certificate;
use NFePHP\Common\DOMImproved as Dom;
use NFePHP\Common\Exception\CertificateException;
use NFePHP\Common\Signer;
use NFePHP\Common\Soap\SoapInterface;
use Umobi\NFSe\Cidades\AbstractCity;
use Umobi\NFSe\Cidades\Goiania;
use Umobi\NFSe\Soap\SoapCurl;

class NFSe {

    protected $config;
    protected $certificate;
    protected $client;

    /** @var AbstractCity */
    protected $city;

    private const CITY_AVAILABLES = [
        'goiania' => Goiania::class
    ];

    public function __construct(array $credentials, array $config, $client = null)
    {
        $this->config = $config;
        $this->validateConfig();

        $this->loadCertificate($credentials);

        $this->loadCity();

        if ($client == null || !$client instanceof SoapInterface) {
            $this->client = new SoapCurl($this->certificate);
            $this->client->disableCertValidation();
        } else {
            $this->client = $client;
        }
    }

    protected function validateConfig()
    {
        if (!isset($this->config['city'])) {
            throw new \Exception("Cidade não configurada.");
        }

        if (!isset($this->config['cnpj']) && !isset($this->config['cpf'])) {
            throw new \Exception("Não informado CNPJ e CPF do Prestador.");
        }

        if (!isset($this->config['im'])) {
            throw new \Exception("Não informado Inscrição municipal do Prestador.");
        }
    }

    protected function loadCity()
    {
        $cityClass = self::CITY_AVAILABLES[$this->config['city']];

        if (!isset($cityClass)) {
            throw new \Exception("Cidade {$this->config['city']} não suportada.");
        }

        $this->city = new $cityClass;
    }

    protected function loadCertificate($credentials)
    {
        if (!isset($credentials['certificate'])) {
            throw new CertificateException("Certificate not configured.");
        }

        $this->certificate = Certificate::readPfx($credentials['certificate'], $credentials['password'] ?? "");
    }

    /**
     * Sign XML passing in content
     * @param string $content
     * @return string XML signed
     */
    public function sign($content)
    {
        $xml = Signer::sign(
            $this->certificate,
            $content,
            "//Rps/*[local-name()='InfDeclaracaoPrestacaoServico']/*[local-name()='Rps']",
            'Id',
            OPENSSL_ALGO_SHA1,
            [true, false, null, null]
        );
        $dom = new Dom('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        return $dom->saveXML($dom->documentElement);
    }

    public function gerarNfse(Rps $rps)
    {
        $dom = $this->city->renderDom($rps, $this->config);
        $xmlsigned = $this->sign($dom->saveXML());

        return $this->city->gerarNfse($this->client, $xmlsigned);
    }

    public function getNFSeHtml($nota, $verificador)
    {
        return $this->city->getNFSeHtml($nota, $verificador, $this->config['im']);
    }

    public function getNFSeLink($nota, $verificador, $im = null)
    {
        $im = $im ?? $this->config['im'];
        return $this->city->getNFSeLink($nota, $verificador, $im);
    }
}