<?php

namespace App\Helpers\Web3Api;


class TxModel
{
    public int $block_number;
    public ?int $block_timestamp;
    public string $hash;
    public ?string $method;
    public ?string $spender;
    public string $from;
    public string $to;
    public float $value;
    public ?string $direct;
    public float $fee;
    public array $contract;
    public string $_raw;

    public function __construct(array $json)
    {
        $this->block_number = (isset($json['block_number'])) ? intval($json['block_number']) : null;
        $this->block_timestamp = (isset($json['block_timestamp'])) ? intval($json['block_timestamp']) : null;
        $this->hash = $json['hash'];
        $this->method = $json['method'] ?? null;
        $this->spender = $json['spender'] ?? null;
        $this->from = $json['from'];
        $this->to = $json['to'];
        $this->value = floatval($json['value']);
        $this->direct = $json['direct'] ?? null;
        $this->fee = floatval($json['fee']);
        $this->contract = $json['contract'];
        $this->_raw = json_encode($json);
    }
}
