<?php

namespace Impala;

interface IFetch {

    public function fetch(IBuilder $builder): array;

    public function sum(IBuilder $builder): int;

}
