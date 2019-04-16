<?php

namespace Impala;

interface IUser {

    public function updateUser(int $id, array $data): int;
}
