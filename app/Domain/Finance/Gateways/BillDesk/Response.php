<?php

namespace Io\Billdesk\Client;

class Response
{
    private $responseStatus;

    private $response;

    private $bdTraceId;

    private $bdTimestamp;

    public function __construct($responseStatus, $response, $bdTraceId, $bdTimestamp)
    {
        $this->responseStatus = $responseStatus;
        $this->response = $response;
        $this->bdTraceId = $bdTraceId;
        $this->bdTimestamp = $bdTimestamp;
    }

    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getBdTraceid()
    {
        return $this->bdTraceId;
    }

    public function getBdTimestamp()
    {
        return $this->bdTimestamp;
    }
}
