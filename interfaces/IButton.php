<?php

namespace Impala;

interface IButton {

    public function getButtons(): array;

    public function push(array $response): array;

}
