<?php

namespace App\Service;

class DB
{
    public function __construct()
    {

    }

    public function initDBUpdate($db): mixed
    {
        $req = $db->prepare("SET FOREIGN_KEY_CHECKS=0;");
        $req->execute([]);
        return $req;
    }

    public function endDBUpdate($db): mixed
    {
        $req = $db->prepare("SET FOREIGN_KEY_CHECKS=1;");
        $req->execute([]);
        return $req;
    }

    public function clearProviderDataInTable($db, $table, $provider_id): mixed
    {
        $req = $db->prepare("
            DELETE FROM $table
            WHERE provider_id = ?;
        ");
        $req->execute(array($provider_id));
        return $req;
    }

    public function createTempTable($db, $table, $temp_table): mixed
    {
        $req = $db->prepare("
            DROP TABLE IF EXISTS $temp_table;
            CREATE TABLE $temp_table LIKE $table;
        ");
        $req->execute();
        return $req;
    }

    /**
     * Crée $temp_table à l'image de $table, compteur AUTO_INCREMENT remis à 1.
     *
     * ATTENTION : ne jamais reporter ici le compteur AUTO_INCREMENT de $table.
     * Le pipeline app:gtfs:update recrée l'intégralité des tables à chaque
     * passage (toutes les 2h en cron). Si le compteur est reporté, il grimpe de
     * "nombre de lignes réimportées" à chaque exécution sans jamais redescendre,
     * alors que le nombre de lignes, lui, reste constant : les id finissent par
     * dépasser la capacité de la colonne. Avec un compteur remis à 1 et une
     * copie qui n'emporte pas l'id (cf. copyTable), le compteur d'une table
     * reconstruite reste de l'ordre de son nombre de lignes, quel que soit le
     * nombre d'imports déjà effectués.
     *
     * (InnoDB sur-alloue les id par lots sur un INSERT ... SELECT, dont il ne
     * connaît pas le nombre de lignes à l'avance : le compteur final vaut
     * quelques fois le nombre de lignes, pas exactement N+1. C'est sans
     * importance, l'essentiel est qu'il ne dépende plus du nombre d'imports.)
     */
    public function perpareTempTable($db, $table, $temp_table): mixed
    {
        $db->executeStatement("DROP TABLE IF EXISTS $temp_table");
        $db->executeStatement("CREATE TABLE $temp_table LIKE $table");

        if ($this->getAutoIncrementColumn($db, $temp_table) !== null) {
            $db->executeStatement("ALTER TABLE $temp_table AUTO_INCREMENT = 1");
        }

        return true;
    }

    public function replaceTempTable($db, $table, $temp_table, $old_table): mixed
    {
        $db->executeStatement("SET FOREIGN_KEY_CHECKS = 0");
        $db->executeStatement("DROP TABLE IF EXISTS $old_table");

        // Un seul RENAME pour les deux tables : MySQL/MariaDB l'exécute de
        // façon atomique, il n'y a donc pas d'instant où $table n'existe pas.
        $db->executeStatement("RENAME TABLE $table TO $old_table, $temp_table TO $table");

        $db->executeStatement("DROP TABLE IF EXISTS $old_table");

        return true;
    }

    public function importFile($db, $type, $path, $header, $set, $sep = ','): mixed
    {
        $table = $type;
        $path = realpath($path);

        $req = $db->prepare("
            LOAD DATA INFILE ?
            INTO TABLE $table
            FIELDS
                TERMINATED BY ?
                ENCLOSED BY '\"'
            LINES
                TERMINATED BY  '\n'
            IGNORE 1 ROWS
            ($header)
            SET $set
        ");
        $req->execute([$path, $sep]);
        return $req;
    }

    public function prefixTable($db, $table, $column, $prefix): mixed
    {
        $prefix_ch = $prefix . '%';

        $req = $db->prepare("
            UPDATE $table
            SET $column = CONCAT(?, $column)
            WHERE $column NOT LIKE ?;
        ");
        $req->execute([$prefix, $prefix_ch]);
        return $req;
    }

    /**
     * Copie $from dans $to en laissant $to réattribuer sa clé auto-incrémentée.
     *
     * On liste explicitement les colonnes au lieu de faire "INSERT INTO $to
     * SELECT * FROM $from" :
     *  - la colonne AUTO_INCREMENT de $to est exclue, donc les id de $to
     *    repartent de son propre compteur au lieu d'être hérités de $from.
     *    C'est ce qui empêche les id de grimper indéfiniment d'un import à
     *    l'autre (cf. perpareTempTable) ;
     *  - la copie ne dépend plus de l'ordre des colonnes des deux tables.
     */
    public function copyTable($db, $from, $to): mixed
    {
        $columns = $this->getCopyColumns($db, $from, $to);

        if ($columns === []) {
            throw new \RuntimeException("Aucune colonne commune entre $from et $to");
        }

        $list = '`' . implode('`, `', $columns) . '`';

        return $db->executeStatement("INSERT INTO $to ($list) SELECT $list FROM $from");
    }

    /**
     * Nom de la colonne AUTO_INCREMENT d'une table, ou null si elle n'en a pas.
     */
    public function getAutoIncrementColumn($db, $table): ?string
    {
        $column = $db->executeQuery(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND EXTRA LIKE '%auto_increment%'
             LIMIT 1",
            [$table]
        )->fetchOne();

        return $column === false || $column === null ? null : (string) $column;
    }

    /**
     * Colonnes présentes dans $from ET dans $to, hors clé auto-incrémentée
     * de $to (que la destination doit générer elle-même).
     *
     * @return string[]
     */
    public function getCopyColumns($db, $from, $to): array
    {
        $fromColumns = $this->getColumnNames($db, $from);
        $toColumns = $this->getColumnNames($db, $to);
        $autoIncrement = $this->getAutoIncrementColumn($db, $to);

        $columns = array_intersect($toColumns, $fromColumns);

        if ($autoIncrement !== null) {
            $columns = array_diff($columns, [$autoIncrement]);
        }

        return array_values($columns);
    }

    /**
     * @return string[]
     */
    public function getColumnNames($db, $table): array
    {
        return $db->executeQuery(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION",
            [$table]
        )->fetchFirstColumn();
    }

    public function tableExists($db, $table): bool
    {
        return (int) $db->executeQuery(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        )->fetchOne() > 0;
    }

    public function dropTable($db, $table): mixed
    {
        return $db->executeStatement("DROP TABLE IF EXISTS $table");
    }

    /**
     * Valeur maximale représentable par le type de la colonne auto-incrémentée.
     */
    public static function getIntegerTypeMax(string $columnType): ?int
    {
        $unsigned = str_contains(strtolower($columnType), 'unsigned');

        $limits = [
            'tinyint'   => [127, 255],
            'smallint'  => [32767, 65535],
            'mediumint' => [8388607, 16777215],
            'int'       => [2147483647, 4294967295],
            'bigint'    => [PHP_INT_MAX, PHP_INT_MAX],
        ];

        foreach ($limits as $type => [$signed, $max]) {
            if (str_starts_with(strtolower($columnType), $type)) {
                return $unsigned ? $max : $signed;
            }
        }

        return null;
    }

    /**
     * État des compteurs AUTO_INCREMENT de la base : pour chaque table qui en a
     * un, le compteur courant, le nombre de lignes et le taux d'occupation du
     * type de la colonne.
     *
     * @return array<int, array{table: string, column: string, column_type: string,
     *                          auto_increment: int, max_value: int|null,
     *                          usage: float|null, rows: int}>
     */
    public function getAutoIncrementStatus($db, bool $includeStaging = false): array
    {
        $rows = $db->executeQuery(
            "SELECT t.TABLE_NAME       AS table_name,
                    c.COLUMN_NAME      AS column_name,
                    c.COLUMN_TYPE      AS column_type,
                    t.AUTO_INCREMENT   AS auto_increment,
                    t.TABLE_ROWS       AS table_rows
             FROM INFORMATION_SCHEMA.TABLES t
             JOIN INFORMATION_SCHEMA.COLUMNS c
               ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
              AND c.TABLE_NAME = t.TABLE_NAME
              AND c.EXTRA LIKE '%auto_increment%'
             WHERE t.TABLE_SCHEMA = DATABASE()
               AND t.AUTO_INCREMENT IS NOT NULL
             ORDER BY t.AUTO_INCREMENT DESC"
        )->fetchAllAssociative();

        $status = [];
        foreach ($rows as $row) {
            $name = (string) $row['table_name'];

            // Les tables de travail du pipeline GTFS sont recréées à chaque
            // import : leur compteur ne dit rien de l'état réel de la base.
            if (!$includeStaging && preg_match('/^(temp_|temp_temp_|old_|compact_|old_compact_)/', $name)) {
                continue;
            }

            $max = self::getIntegerTypeMax((string) $row['column_type']);
            $autoIncrement = (int) $row['auto_increment'];

            $status[] = [
                'table'          => $name,
                'column'         => (string) $row['column_name'],
                'column_type'    => (string) $row['column_type'],
                'auto_increment' => $autoIncrement,
                'max_value'      => $max,
                'usage'          => $max ? ($autoIncrement / $max) * 100 : null,
                'rows'           => (int) $row['table_rows'],
            ];
        }

        return $status;
    }

    /**
     * Définitions des clés étrangères portées par une table, telles qu'écrites
     * dans SHOW CREATE TABLE.
     *
     * On repart du DDL plutôt que d'INFORMATION_SCHEMA parce qu'il conserve
     * les clauses ON DELETE / ON UPDATE et les contraintes multi-colonnes,
     * que l'on doit rejouer à l'identique après un échange de table.
     *
     * @return array<int, array{table: string, name: string, definition: string, references: string}>
     */
    public function getForeignKeyDefinitions($db, $table): array
    {
        $ddl = $db->executeQuery("SHOW CREATE TABLE $table")->fetchAssociative();

        if ($ddl === false) {
            return [];
        }

        $create = (string) ($ddl['Create Table'] ?? array_values($ddl)[1] ?? '');

        preg_match_all(
            '/CONSTRAINT `([^`]+)` (FOREIGN KEY [^\n]*?)(?:,\s*$|\s*$)/m',
            $create,
            $matches,
            PREG_SET_ORDER
        );

        $definitions = [];
        foreach ($matches as $match) {
            $definition = rtrim(trim($match[2]), ',');

            preg_match('/REFERENCES `([^`]+)`/', $definition, $reference);

            $definitions[] = [
                'table'      => (string) $table,
                'name'       => $match[1],
                'definition' => $definition,
                'references' => $reference[1] ?? '',
            ];
        }

        return $definitions;
    }

    /**
     * Toutes les clés étrangères d'autres tables qui pointent vers $table,
     * avec leur définition complète pour pouvoir les rejouer à l'identique.
     *
     * Nécessaire avant un RENAME TABLE : MySQL/MariaDB fait suivre les clés
     * étrangères entrantes sur la table renommée, ce qui laisserait les tables
     * référençantes accrochées à l'ancienne table.
     *
     * @return array<int, array{table: string, name: string, definition: string, references: string}>
     */
    public function getIncomingForeignKeyDefinitions($db, $table): array
    {
        $referencing = $db->executeQuery(
            "SELECT DISTINCT TABLE_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?
               AND TABLE_NAME <> ?",
            [$table, $table]
        )->fetchFirstColumn();

        $definitions = [];
        foreach ($referencing as $referencingTable) {
            foreach ($this->getForeignKeyDefinitions($db, $referencingTable) as $definition) {
                if ($definition['references'] === $table) {
                    $definitions[] = $definition;
                }
            }
        }

        return $definitions;
    }

    public function dropForeignKey($db, $table, $name): mixed
    {
        return $db->executeStatement("ALTER TABLE `$table` DROP FOREIGN KEY `$name`");
    }

    /**
     * @param array{table: string, name: string, definition: string} $definition
     */
    public function addForeignKey($db, array $definition): mixed
    {
        return $db->executeStatement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` %s',
            $definition['table'],
            $definition['name'],
            $definition['definition']
        ));
    }

    public function countRows($db, $table): int
    {
        return (int) $db->executeQuery("SELECT COUNT(*) FROM `$table`")->fetchOne();
    }

    /**
     * Clés étrangères d'autres tables qui pointent vers ($table, $column).
     *
     * @return array<int, array{table: string, column: string, constraint: string}>
     */
    public function getIncomingForeignKeys($db, $table, $column): array
    {
        $rows = $db->executeQuery(
            "SELECT TABLE_NAME AS table_name,
                    COLUMN_NAME AS column_name,
                    CONSTRAINT_NAME AS constraint_name
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?
               AND REFERENCED_COLUMN_NAME = ?",
            [$table, $column]
        )->fetchAllAssociative();

        return array_map(static fn(array $r): array => [
            'table'      => (string) $r['table_name'],
            'column'     => (string) $r['column_name'],
            'constraint' => (string) $r['constraint_name'],
        ], $rows);
    }

    public function truncateTable($db, $table): mixed
    {
        $req = $db->prepare("
            TRUNCATE $table;
        ");
        $req->execute([]);
        return $req;
    }

    public function generateTempStopRoute($db): mixed
    {
        $req = $db->prepare("
            INSERT INTO temp_stop_route
            (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, location_type)
            
            SELECT DISTINCT 
            CONCAT(R.route_id, '-', S2.stop_id) as route_key, R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color, S2.stop_id, S2.stop_name, S2.stop_name, S2.stop_lat, S2.stop_lon, S2.location_type
            FROM routes R
            
            INNER JOIN trips T
            ON R.route_id = T.route_id
            
            INNER JOIN stop_times ST
            ON T.trip_id = ST.trip_id
            
            INNER JOIN stops S
            ON ST.stop_id = S.stop_id
            
            INNER JOIN stops S2
            ON S.parent_station = S2.stop_id;
        ");
        $req->execute([]);
        return $req;
    }

    public function generateTempStopRoute2($db): mixed
    {
        $req = $db->prepare("
            INSERT INTO temp_stop_route
            (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, location_type)
            
            SELECT DISTINCT 
            CONCAT(R.route_id, '-', S.stop_id) as route_key, R.route_id, R.route_short_name, R.route_long_name, R.route_type, R.route_color, R.route_text_color, S.stop_id, S.stop_name, S.stop_name, S.stop_lat, S.stop_lon, S.location_type
            FROM routes R
            
            INNER JOIN trips T
            ON R.route_id = T.route_id
            
            INNER JOIN stop_times ST
            ON T.trip_id = ST.trip_id
            
            INNER JOIN stops S
            ON ST.stop_id = S.stop_id

            WHERE location_type = '0'
            AND ST.pickup_type != '1';
        ");
        $req->execute([]);
        return $req;
    }

    public function autoDeleteStopRoute($db): mixed
    {
        $req = $db->prepare("
            DELETE FROM stop_route 
            WHERE route_key NOT IN (SELECT route_key FROM temp_stop_route);
        ");
        $req->execute([]);
        return $req;
    }

    public function autoInsertStopRoute($db): mixed
    {
        $req = $db->prepare("
            INSERT INTO stop_route (route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code, location_type)
            
            SELECT route_key, route_id, route_short_name, route_long_name, route_type, route_color, route_text_color, stop_id, stop_name, stop_query_name, stop_lat, stop_lon, town_id, town_name, town_query_name, zip_code, location_type
            FROM temp_stop_route
            WHERE route_key NOT IN (
                SELECT route_key
                FROM stop_route
            );
        ");
        $req->execute([]);
        return $req;
    }

    public function prepareStopRoute($db): mixed
    {
        $req = $db->prepare("
            UPDATE stop_route SR 
            SET SR.stop_lat = NULL,
                SR.stop_lon = NULL
            WHERE SR.stop_lat IS NULL
                OR SR.stop_lon IS NULL
                OR SR.stop_lat = ''
                OR SR.stop_lon = '';
        ");
        $req->execute(array());
        return $req;
    }

    public function generateQueryRoute($db): mixed
    {
        $req = $db->prepare("
            SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '-', '');
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, ' ', '');
            UPDATE stop_route SET stop_query_name = REPLACE(stop_query_name, '\'', '');
            
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '-', '');
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, ' ', '');
            UPDATE stop_route SET town_query_name = REPLACE(town_query_name, '\'', '');
        ");
        $req->execute(array());
        return $req;
    }

    public function getColumns($db, $table): mixed
    {
        $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
        ";
        $statement = $db->executeQuery($query);
        return $statement->fetchAllAssociative();
    }

    public function getConstraints($db, $db_name): mixed
    {
        $query = "
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_SCHEMA = '$db_name';
        ";
        $statement = $db->executeQuery($query);
        return $statement->fetchAllAssociative();
    }

    public function removeConstraints($db, $table, $name): mixed
    {
        return $db->executeStatement("ALTER TABLE $table DROP FOREIGN KEY $name");
    }

    public function createConstraints($db, $table, $column, $name, $referenced_table, $referenced_name): mixed
    {
        return $db->executeStatement(
            "ALTER TABLE $table
             ADD CONSTRAINT $name
             FOREIGN KEY ($column) REFERENCES $referenced_table ($referenced_name)
             ON DELETE CASCADE"
        );
    }
}
