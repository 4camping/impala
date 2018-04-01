<?php

namespace Impala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class KeywordsRepository extends BaseRepository {

    public function getKeyword(string $keyword, array $used): IRow {
        $resource = $this->database->table($this->source)
                        ->where('content LIKE',  '%' . strtolower($keyword) . '%');
        foreach($used as $usage) {
            $resource->where('content NOT LIKE', '%' . strtolower($usage) . '%');
        }
        return $resource->fetch();
    }

}
