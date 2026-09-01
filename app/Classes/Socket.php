<?php

namespace App\Classes;

use phpDocumentor\Reflection\Types\Self_;

class Socket
{
    private $sk;
    public function __construct($host, $port)
    {
        $timeout=30;
        $sk = fsockopen($host,$port,$errorCode,$errorMessage, $timeout);

        if (!is_resource($sk)) {
            exit("connection fail: ".$errorCode." ".$errorMessage) ;
        }
        $this->sk =  $sk;
    }

    public function send($message)
    {
        fwrite($this->sk, "[127.0.0.1]:".$message, 99999);
    }
}
