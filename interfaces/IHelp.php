<?php

namespace Impala;

interface IHelp {

    public function getHelp(string $controller, string $action, string $parameters): array;
    
}
