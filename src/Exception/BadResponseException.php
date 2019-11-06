<?php

namespace Umobi\NFSe\Exception;

class BadResponseException extends \RuntimeException
{
    public $response;
    public $responseString;

    public function __construct($message, $response = null, $responseString = null) {
        $code = preg_replace("/\D+/", "", $response->ListaMensagemRetorno->MensagemRetorno->Codigo ?? "") ?? -1;
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->responseString = $responseString;
    }
}