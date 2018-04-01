<?php

namespace Impala;

/** @author Lubomir Andrisek */
final class WriteRepository extends BaseRepository {

    public function updateWrite(string $keyword, array $data): int {
        return $this->database->table($this->source)
                        ->where('keyword', $keyword)
                        ->update($data);
    }

}
