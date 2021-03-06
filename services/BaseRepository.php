<?php

namespace Impala;

use Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\Caching\IStorage;

/** @author Lubomir Andrisek */
abstract class BaseRepository {

    /** @var Cache */
    public $cache;

    /** @var Context */
    public $database;

    /** @var string */
    public $source;

    public function __construct($source = null, Context $database, IStorage $storage) {
        $this->source = $source;
        $this->database = $database;
        $this->cache = new Cache($storage);
    }

    public function getSource(): string {
        return $this->source;
    }

}
