<?php

namespace Impala;

interface IHelp {

    function getHelp(string $controller, string $action, string $parameters): array;
    
}
