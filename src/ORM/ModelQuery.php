<?php

declare(strict_types=1);

namespace Tempest\ORM;

use PDO;
use Tempest\Interfaces\Model;
use Tempest\Interfaces\Query;

/**
 * @template ModelClass
 */
final class ModelQuery implements Query
{
    private array $select = [];
    private array $from = [];
    private array $where = [];
    private array $orderBy = [];
    private ?int $limit = null;

    private function __construct(
        private readonly string $modelClass,
    ) {
    }

    /**
     * @template ModelClassName
     * @param class-string<ModelClassName> $modelClass
     * @return self<ModelClassName>
     */
    public static function new(string $modelClass): self
    {
        return new self($modelClass);
    }

    public function select(string|FieldName ...$statements): Query
    {
        foreach ($statements as $statement) {
            $this->select[] = $statement;
        }

        return $this;
    }

    public function from(TableName $table): Query
    {
        $this->from[] = $table;

        return $this;
    }

    public function join(TableName $table, FieldName $left, FieldName $right): Query
    {
        $this->from[] = "INNER JOIN {$table} ON {$left} = {$right}";

        return $this;
    }

    public function where(FieldName $field, mixed $value): Query
    {
        $this->where[] = "{$field} = {$value}";

        return $this;
    }

    public function orderBy(FieldName $field, Direction $direction = Direction::ASC): Query
    {
        $this->orderBy[] = "{$field} {$direction->value}";

        return $this;
    }

    public function limit(int $limit): Query
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return ModelClass|null
     */
    public function first(): ?Model
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * @return ModelClass[]
     */
    public function get(): array
    {
        $pdo = get(PDO::class);

        return array_map(
            fn (array $item) => $this->resolveModel($item),
            $pdo->query($this->buildSelect())->fetchAll(PDO::FETCH_NAMED),
        );
    }

    public function insert(...$params): string
    {
        $pdo = get(PDO::class);

        $columns = implode(', ', array_keys($params));

        $valuePlaceholders = implode(', ', array_map(
            fn (string $key) => ":{$key}",
            array_keys($params),
        ));

        $query = "INSERT INTO {$this->table()} ({$columns}) VALUES ({$valuePlaceholders})";

        $pdo->prepare($query)->execute($params);

        return $pdo->lastInsertId();
    }

    public function buildSelect(): string
    {
        // TODO prepared statements
        $statements = [];

        if ($this->select !== []) {
            $statements[] = 'SELECT ' . implode(', ', $this->select);
        } else {
            $statements[] = 'SELECT *';
        }

        if ($this->from !== []) {
            $statements[] = 'FROM ' . implode(' ', $this->from);
        } else {
            $statements[] = 'FROM ' . $this->table();
        }

        if ($this->where !== []) {
            $statements[] = 'WHERE ' . implode(' ', $this->where);
        }

        if ($this->orderBy !== []) {
            $statements[] = 'ORDER BY ' . implode(' ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $statements[] = 'LIMIT ' . $this->limit;
        }

        return trim(implode(PHP_EOL, $statements)) . ';';
    }

    private function table(): TableName
    {
        return call_user_func("{$this->modelClass}::table");
    }

    private function field(string $field): FieldName
    {
        return new FieldName(
            tableName: $this->table(),
            fieldName: $field,
        );
    }

    /**
     * @param array $item
     * @return ModelClass
     */
    private function resolveModel(array $item): Model
    {
        $model = new ($this->modelClass)();

        foreach ($item as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }
}