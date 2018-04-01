<?php

namespace Impala;

interface IMock {

    public function getTestRow(string $table, array $clauses = []): array;

    public function getTestRows(string $table, array $clauses = [], int $limit): array;
    
}
