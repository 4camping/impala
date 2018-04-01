<?php

namespace Impala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class MockRepository extends BaseRepository implements IMock {

    public function explainColumn(string $table, string $column): IRow {
        return $this->database->query('EXPLAIN SELECT `' . $column . '` FROM `' . $table . '`')->fetch();
    }

    public function getColumns(string $table): array {
        return $this->database->getConnection()
                        ->getSupplementalDriver()
                        ->getColumns($table);
    }

    public function getDuplicity(string $table, string $group, array $columns): IRow {
        $resource = $this->database->table((string) $table)
                        ->select($group . ', COUNT(id) AS sum');
        foreach($columns as $column => $value) {
            $resource->where($column, $value);
        }
        return $resource->fetch();
    }

    public function getTestRow(string $collection, array $filters = []): array {
        $sum = $this->client->selectCollection($this->database, $collection)
            ->aggregate([['$group' => ['_id' => null, 'total' => ['$sum' => 1]]]])
            ->toArray();
        $sum = reset($sum)->total;
        if(null == $row = $this->client->selectCollection($this->database, $collection)
                        ->findOne($filters, ['skip' => rand(0, $sum - 1)])) {
            return [];
        }
        return $row->getArrayCopy();
    }

    public function getTestRows(string $table, array $clauses = [], int $limit): array {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->limit($limit)
                        ->fetchAll();
    }

    public function getTestCollections(): array {
        $collections = [];
        $list = $this->client->selectDatabase($this->database)->listCollections();
        foreach($list as $collection) {
            $collections[] = $collection->getName();
        }
        return $collections;
    }

    public function updateTestRow(string $table, array $data, array $clauses = []): int {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->update($data);
    }

    public function removeTestRow(string $table, array $clauses = []): int {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->delete();
    }

}