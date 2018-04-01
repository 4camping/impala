<?php

namespace Impala;

interface IImport {

    public function resize(array $response): array;
    
    public function save(array $response): array;

}
