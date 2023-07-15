<?php

declare(strict_types=1);

namespace Tempest\Database\Migrations;

use PDO;
use Tempest\Database\Builder\TableBuilder;
use Tempest\Database\DatabaseConfig;
use Tempest\Interfaces\Container;
use Tempest\Interfaces\Migration as MigrationInterface;

final readonly class MigrationManager
{
    public function __construct(
        private Container $container,
        private DatabaseConfig $databaseConfig,
        private PDO $pdo,
    ) {
    }

    public function up(): void
    {
        try {
            $existingMigrations = Migration::query()->get();
        } catch (\PDOException) {
            $this->executeUp(new CreateMigrationsTable());

            $existingMigrations = Migration::query()->get();
        }

        $existingMigrations = array_map(
            fn (Migration $migration) => $migration->name,
            $existingMigrations,
        );

        foreach ($this->databaseConfig->migrations as $migrationClassName) {
            /** @var MigrationInterface $migration */
            $migration = $this->container->get($migrationClassName);

            if (in_array($migration->getName(), $existingMigrations)) {
                continue;
            }

            $this->executeUp($migration);
        }
    }

    public function executeUp(MigrationInterface $migration): void
    {
        $tableBuilder = $migration->up(new TableBuilder());

        $this->pdo->query($tableBuilder->getQuery())->execute();

        Migration::create(
            name: $migration->getName(),
        );
    }
}