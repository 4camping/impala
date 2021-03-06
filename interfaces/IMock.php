<?php

namespace Impala;

use Nette\Database\Table\IRow;

interface IMock {

    public function getTestRow(string $table, array $clauses = []): IRow;

    public function getTestRows(string $table, array $clauses, int $limit): array;
    
}
