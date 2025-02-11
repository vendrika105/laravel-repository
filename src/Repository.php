<?php

namespace Vendrika105\LaravelRepository;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class Repository
{
    protected string $table_name;

    protected string $connection_name;

    protected Connection $connection;

    public function getTableName(): string
    {
        return $this->table_name;
    }

    public function setTableName(string $table_name): static
    {
        $this->table_name = $table_name;

        return $this;
    }

    public function getConnectionName(): string
    {
        return $this->connection_name;
    }

    public function setConnectionName(string $connection_name): static
    {
        $this->connection_name = $connection_name;

        return $this->createConnection();
    }

    public function createConnection(): static
    {
        $this->connection = DB::connection($this->getConnectionName());

        return $this;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}