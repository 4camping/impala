<?php

namespace Impala;

use MongoDB\Client,
    MongoDB\Collection,
    Nette\Caching\Cache,
    Nette\Caching\IStorage;

/** @author Lubomir Andrisek */
class BaseRepository {

    /** @var Cache */
    protected $cache;

    /** @var Collection */
    protected $collection;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $database;

    /** @var string */
    protected $source;

    public function __construct(string $database, string $source, IBuilder $grid, Client $client, IStorage $storage) {
        $this->cache = new Cache($storage);
        $this->client = $client;
        $this->collection = $client->selectCollection($database, $source);
        $this->database = $database;
        $this->source = $source;
    }

}
