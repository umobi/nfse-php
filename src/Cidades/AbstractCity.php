<?php

namespace Umobi\NFSe\Cidades;

use NFePHP\Common\DOMImproved;
use Umobi\NFSe\Rps;

abstract class AbstractCity
{
    public abstract function renderDom(Rps $rps, $config = null): DOMImproved;
    public abstract function renderConsultaDom(Rps $rps, $config = null): DOMImproved;

    public abstract function gerarNfse($client, $xmlSigned);

    public abstract function consultarNfse($client, $xmlSigned);

    public abstract function getNFSeHtml($nota, $verificador, $inscricao);

    public abstract function getNFSeLink($nota, $verificador, $inscricao);
}
