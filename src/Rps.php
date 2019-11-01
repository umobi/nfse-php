<?php


namespace Umobi\NFSe;


class Rps
{
    public $version = '2.00'; //indica qual JsonSchema USAR na validação

    public $naturezaOperacao = 1; // 1 – Tributação no município
        // 2 - Tributação fora do município
        // 3 - Isenção
        // 4 - Imune
        // 5 – Exigibilidade suspensa por decisão judicial
        // 6 – Exigibilidade suspensa por procedimento administrativo

    public $regimeEspecialTributacao = 1;     // 1 – Microempresa municipal
        // 2 - Estimativa
        // 3 – Sociedade de profissionais
        // 4 – Cooperativa
        // 5 – MEI – Simples Nacional
        // 6 – ME EPP – Simples Nacional

    public $optanteSimplesNacional = 1; //1 - SIM 2 - Não
    public $incentivadorCultural = 2; //1 - SIM 2 - Não
    public $status = 1;  // 1 – Normal  2 – Cancelado
    public $dataEmissao;

    public $identificacaoRpsNumero; //limite 15 digitos
    public $identificacaoRpsSerie = 'UNICA'; //BH deve ser string numerico
    public $identificacaoRpsTipo = 1; //1 - RPS 2-Nota Fiscal Conjugada (Mista) 3-Cupom

    public $tomadorCnpj;
    public $tomadorCpf;

    public $tomadorRazaoSocial;
    public $tomadorInscricaoMunicipal;

    public $tomadorEnderecoEndereco;
    public $tomadorEnderecoNumero;
    public $tomadorEnderecoComplemento;
    public $tomadorEnderecoBairro;
    public $tomadorEnderecoCodigoMunicipio;
    public $tomadorEnderecoUf;
    public $tomadorEnderecoCep;

    public $servicoCodigoTributacaoMunicipio;
    public $servicoDiscriminacao;
    public $servicoCodigoMunicipio;

    public $servicoValoresValorServicos;
    public $servicoValoresValorDeducoes;
    public $servicoValoresValorPis;
    public $servicoValoresValorCofins;
    public $servicoValoresValorInss;
    public $servicoValoresValorIr;
    public $servicoValoresValorCsll;
    public $servicoValoresIssRetido;
    public $servicoValoresValorIss;
    public $servicoValoresOutrasRetencoes;
    public $servicoValoresAliquota;
    public $servicoValoresDescontoIncondicionado;
    public $servicoValoresDescontoCondicionado;
}