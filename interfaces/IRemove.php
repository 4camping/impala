<?php

namespace Impala;

interface IRemove {

    public function remove(string $primary, array $response): array;

}
