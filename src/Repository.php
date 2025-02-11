<?php

namespace Vendrika105\LaravelRepository;

class Repository
{
    protected string $table_name;

    protected string $connection_name;

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

        return $this;
    }
}