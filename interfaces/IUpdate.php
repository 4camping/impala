<?php

namespace Impala;

interface IUpdate {

    public function update(string $key, array $data): array;

}
