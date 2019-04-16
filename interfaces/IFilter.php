<?php

namespace Impala;

interface IFilter {

    public function getList(string $alias): array;

    public function filter(array $filters): array;

}
