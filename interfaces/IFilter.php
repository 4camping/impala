<?php

namespace Impala;

interface IFilter {

    public function getList($alias): array;

    public function filter(array $filters): array;

}
