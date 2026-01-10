<?php

namespace Io\Billdesk\Client;

interface BillDeskClient
{
    public function createOrder($request, $headers = []);

    public function createTransaction($request, $headers = []);

    public function refundTransaction($request, $headers = []);
}
