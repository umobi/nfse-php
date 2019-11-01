<?php

namespace Umobi\NFSe\Cidades;

use NFePHP\Common\DOMImproved;
use NFePHP\Common\Soap\SoapCurl;
use Umobi\NFSe\Rps;

class Goiania extends AbstractCity
{
    private $wsUrl = "https://nfse.goiania.go.gov.br/ws/nfse.asmx";
    private $messageNamespace = "http://nfse.goiania.go.gov.br/xsd/nfse_gyn_v02.xsd";
    private $soapNamespace = "http://nfse.goiania.go.gov.br/ws/";


    /** @var DOMImproved */
    protected $dom;

    /** @var \DOMElement */
    protected $rpsDoc;

    public function __construct()
    {

        $this->dom = new DOMImproved('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
        $this->rpsDoc = $this->dom->createElement('Rps');
    }

    public function gerarNfse($client, $xmlSigned)
    {

        $operation = "GerarNfse";
        $content = "<{$operation}Envio xmlns=\"{$this->messageNamespace}\">" . $xmlSigned . "</{$operation}Envio>";

        return $this->send($client, $content, $operation);
    }

    public function getNFSeLink($nota, $verificador, $inscricao)
    {
        return "http://www2.goiania.go.gov.br/sistemas/snfse/asp"
            . "/snfse00200w0.asp?"
            . "inscricao=$inscricao"
            . "&nota=$nota"
            . "&verificador=$verificador";
    }

    public function getNFSeHtml($nota, $verificador, $inscricao)
    {
        $url = $this->getNFSeLink($nota, $verificador, $inscricao);

        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = (string) curl_exec($ch);
        curl_close($ch);
        $path = "http://www2.goiania.go.gov.br";
        $data = str_replace(
            '<img src="/sistemas/saces/imagem/brasao.gif" width="70">',
            "<img src=\"$path/sistemas/saces/imagem/brasao.gif\" width=\"70\">",
            $data
        );
        $data = str_replace(
            '<img src="/sistemas/snfse/imagem/email_link.png" '
            . 'class="some" title="Link desta nota para envio ao tomador" '
            . 'border="0">',
            "<img src=\"$path/sistemas/snfse/imagem/email_link.png\" "
            . "class=\"some\" title=\"Link desta nota para envio ao tomador\" "
            . "border=\"0\">",
            $data
        );

        return utf8_encode($data);
    }

    /**
     * @param SoapCurl $client
     * @param $content
     * @param $operation
     */
    private function send($client, $content, $operation)
    {
        $action = "{$this->soapNamespace}$operation";
        $request = $this->createSoapRequest($content, $operation);

        $headers = [
            "SOAPAction: \"$action\"",
        ];

        $envNamespaces = [
            'xmlns:soap' => 'http://www.w3.org/2003/05/soap-envelope'
        ];

        $response = $client->send(
            $this->wsUrl, $operation, $action,
            SOAP_1_2, $headers, $envNamespaces, $request
        );


        return $this->extractContentFromResponse($response, $operation);
    }

    protected function createSoapRequest($message, $operation)
    {
        $env = "<$operation xmlns=\"". $this->soapNamespace . "\">"
            . "<ArquivoXML></ArquivoXML>"
            . "</$operation>";

        $dom = new DOMImproved('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($env);
        $node = $dom->getElementsByTagName('ArquivoXML')->item(0);
        $cdata = $dom->createCDATASection($message);
        $node->appendChild($cdata);
        return $dom->saveXML($dom->documentElement);
    }

    protected function extractContentFromResponse($response, $operation)
    {
        $dom = new DOMImproved('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($response);
        $node = !empty($dom->getElementsByTagName("{$operation}Result")->item(0))
            ? $dom->getElementsByTagName("{$operation}Result")->item(0)
            : null;
        if (empty($node)) {
            return $response;
        } else {
            return $node->textContent;
        }
    }

    public function renderDom(Rps $rps, $config = null): DOMImproved
    {
        $infRps = $this->dom->createElement('InfDeclaracaoPrestacaoServico');
        $att = $this->dom->createAttribute('xmlns');
        $att->value = 'http://nfse.goiania.go.gov.br/xsd/nfse_gyn_v02.xsd';
        $infRps->appendChild($att);

        $innerRPS = $this->dom->createElement('Rps');
        $att = $this->dom->createAttribute('Id');
        $att->value = $rps->identificacaoRpsNumero;
        $innerRPS->appendChild($att);

        $this->addIdentificacao($innerRPS, $rps, $config);

        $this->dom->addChild(
            $innerRPS,
            "DataEmissao",
            $rps->dataEmissao,
            true
        );
        $this->dom->addChild(
            $innerRPS,
            "Status",
            $rps->status,
            true
        );
        $infRps->appendChild($innerRPS);

        $this->addServico($infRps, $rps);

        if (isset($config) && is_array($config)) {
            $this->addPrestador($infRps, $rps, $config);
        }
        $this->addTomador($infRps, $rps);

        $this->rpsDoc->appendChild($infRps);
        $this->dom->appendChild($this->rpsDoc);

        return $this->dom;
    }

    protected function addIdentificacao(&$parent, Rps $rps, $config = null)
    {
        $nfSerie = isset($config['dry_run']) && $config['dry_run'] ? "TESTE": $rps->identificacaoRpsSerie;

        $node = $this->dom->createElement('IdentificacaoRps');
        $this->dom->addChild(
            $node,
            "Numero",
            $rps->identificacaoRpsNumero,
            true
        );
        $this->dom->addChild(
            $node,
            "Serie",
            $nfSerie,
            true
        );
        $this->dom->addChild(
            $node,
            "Tipo",
            $rps->identificacaoRpsTipo,
            true
        );
        $parent->appendChild($node);
    }

    protected function addServico(&$parent, Rps $rps)
    {
        $node = $this->dom->createElement('Servico');
        $valnode = $this->dom->createElement('Valores');
        $this->dom->addChild(
            $valnode,
            "ValorServicos",
            number_format($rps->servicoValoresValorServicos, 2, '.', ''),
            true
        );
        $this->dom->addChild(
            $valnode,
            "ValorPis",
            isset($rps->servicoValoresValorPis)
                ? number_format($rps->servicoValoresValorPis, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorCofins",
            isset($rps->servicoValoresValorCofins)
                ? number_format($rps->servicoValoresValorCofins, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorInss",
            isset($rps->servicoValoresValorInss)
                ? number_format($rps->servicoValoresValorInss, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorCsll",
            isset($rps->servicoValoresValorCsll)
                ? number_format($rps->servicoValoresValorCsll, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "OutrasRetencoes",
            isset($rps->servicoValoresOutrasRetencoes)
                ? number_format($rps->servicoValoresOutrasRetencoes, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorIss",
            isset($rps->servicoValoresValorIss)
                ? number_format($rps->servicoValoresValorIss, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "Aliquota",
            isset($rps->servicoValoresAliquota)
                ? number_format($rps->servicoValoresAliquota, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "DescontoIncondicionado",
            isset($rps->servicoValoresDescontoIncondicionado)
                ? number_format($rps->servicoValoresDescontoIncondicionado, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "DescontoCondicionado",
            isset($rps->servicoValoresDescontoCondicionado)
                ? number_format($rps->servicoValoresDescontoCondicionado, 2, '.', '')
                : null,
            false
        );

        $node->appendChild($valnode);

        $this->dom->addChild(
            $node,
            "CodigoTributacaoMunicipio",
            $rps->servicoCodigoTributacaoMunicipio,
            true
        );
        $this->dom->addChild(
            $node,
            "Discriminacao",
            $rps->servicoDiscriminacao,
            true
        );
        $this->dom->addChild(
            $node,
            "CodigoMunicipio",
            $rps->servicoCodigoMunicipio,
            true
        );
        $parent->appendChild($node);
    }

    protected function addPrestador(&$parent, Rps $rps, $config)
    {

        $node = $this->dom->createElement('Prestador');
        $cpfcnpj = $this->dom->createElement('CpfCnpj');

        if (isset($config['cnpj']) && !empty($config['cnpj'])) {
            $this->dom->addChild(
                $cpfcnpj,
                "Cnpj",
                $config['cnpj'],
                true
            );
        } else {
            $this->dom->addChild(
                $cpfcnpj,
                "Cpf",
                $config['cpf'],
                true
            );
        }
        $node->appendChild($cpfcnpj);
        $this->dom->addChild(
            $node,
            "InscricaoMunicipal",
            $config['im'],
            true
        );

        $parent->appendChild($node);
    }

    protected function addTomador(&$parent, Rps $rps)
    {
        if (!isset($rps->tomadorRazaoSocial)) {
            return;
        }

        $node = $this->dom->createElement('Tomador');
        $ide = $this->dom->createElement('IdentificacaoTomador');
        $cpfcnpj = $this->dom->createElement('CpfCnpj');
        if (isset($rps->tomadorCnpj)) {
            $this->dom->addChild(
                $cpfcnpj,
                "Cnpj",
                $rps->tomadorCnpj,
                true
            );
        } else {
            $this->dom->addChild(
                $cpfcnpj,
                "Cpf",
                $rps->tomadorCpf,
                true
            );
        }
        $ide->appendChild($cpfcnpj);
        $this->dom->addChild(
            $ide,
            "InscricaoMunicipal",
            isset($rps->tomadorInscricaoMunicipal) ? $rps->tomadorInscricaoMunicipal : null,
            false
        );
        $node->appendChild($ide);
        $this->dom->addChild(
            $node,
            "RazaoSocial",
            $rps->tomadorRazaoSocial,
            true
        );
        if (!empty($rps->tomadorEnderecoEndereco)) {
            $endereco = $this->dom->createElement('Endereco');
            $this->dom->addChild(
                $endereco,
                "Endereco",
                $rps->tomadorEnderecoEndereco,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Numero",
                $rps->tomadorEnderecoNumero,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Complemento",
                isset($rps->tomadorEnderecoComplemento) ? $rps->tomadorEnderecoComplemento : null,
                false
            );
            $this->dom->addChild(
                $endereco,
                "Bairro",
                $rps->tomadorEnderecoBairro,
                true
            );
            if (isset($rps->tomadorEnderecoCodigoMunicipio)) {
                $this->dom->addChild(
                    $endereco,
                    "CodigoMunicipio",
                    $rps->tomadorEnderecoCodigoMunicipio,
                    true
                );
            }
            $this->dom->addChild(
                $endereco,
                "Uf",
                $rps->tomadorEnderecoUf,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Cep",
                !empty($rps->tomadorEnderecoCep) ? $rps->tomadorEnderecoCep : null,
                false
            );
            $node->appendChild($endereco);
        }
        $parent->appendChild($node);
    }
}