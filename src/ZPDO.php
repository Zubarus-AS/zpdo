<?php

declare(strict_types=1);

namespace Zubarus\ZPDO;

use PDO;
use PDOException;

class ZPDO extends PDO
{
    /**
     * Constructs a new ZPDO instance using configuration from an INI file.
     *
     * @param string $configPath Path to the INI configuration file.
     * @throws ZPDOException If the configuration file cannot be parsed or if the PDO connection fails.
     */
    public function __construct(string $configPath)
    {
        $config = parse_ini_file($configPath, true, INI_SCANNER_TYPED);
        if ($config === false) {
            throw new ZPDOException("Could not parse config file.");
        }

        try {
            parent::__construct(
                "mysql:host=" . $config["database"]["db_host"] . ";dbname=" . $config["database"]["db_name"],
                $config["database"]["db_user"], 
                $config["database"]["db_pass"],
            );
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new ZPDOException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Retrieves the value(s) of a specified column or columns from a table row identified by a key column and value.
     *
     * @param string|array $fetchColumn The column name as a string, or an array of column names to fetch.
     * @param string $table The name of the table to query.
     * @param string $keyColumn The column name used as the key for the WHERE clause.
     * @param string|int $keyValue The value to match in the key column.
     * @return string|int|null|array<string|int|null>|false Returns the column value if a single column is requested, or an associative array of column values if multiple columns are requested.
     *                                    Returns false if no row is found.
     * @throws ZPDOException If a PDOException occurs during query execution.
     */
    public function getColumnValue(
        string|array $fetchColumn,
        string $table,
        string $keyColumn,
        string|int $keyValue,
    ): string|int|null|array|false {
        if (is_string($fetchColumn)) {
            $fc = $fetchColumn;
        } else {
            $fc = implode(",", $fetchColumn);
        }
        
        try {
            $stmt = $this->prepare(<<<SQL
                SELECT {$fc}
                FROM {$table}
                WHERE {$keyColumn} = :key_value
            SQL);
            $stmt->execute([ "key_value" => $keyValue ]);
            
            if (is_string($fetchColumn)) {
                return $stmt->fetchColumn();
            } else {
                return $stmt->fetch();
            }
        } catch (PDOException $e) {
            throw new ZPDOException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
