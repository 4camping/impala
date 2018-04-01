<?php

namespace Impala;

/** @author Lubomir Andrisek */
final class HelpRepository extends BaseRepository implements IHelp {

    public function getHelp(string $controller, string $action, string $parameters): array {
        if (null == $help = $this->collection->findOne(['source' => ['$in' =>
            [$controller, $controller . ':' . $action, $controller . ':' . $action . ':' . $parameters]]])) {
            return [];
        }
        return (array) json_decode($help->json);
    }

}
