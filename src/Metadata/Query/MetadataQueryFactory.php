<?php

declare(strict_types=1);

namespace Keboola\DbExtractor\Metadata\Query;

use Keboola\DbExtractor\Adapter\Connection\DbConnection;
use Keboola\DbExtractor\Exception\InvalidStateException;
use Keboola\DbExtractorConfig\Configuration\ValueObject\DatabaseConfig;
use Keboola\DbExtractorConfig\Configuration\ValueObject\InputTable;

/**
 * This class generates database specific SQLs to load tables and columns metadata.
 * Code is database specific and cannot be universal for all ODBC drivers.
 * Universal methods such as odbc_columns don't work reliably (e.g. encoding problems.).
 */
class MetadataQueryFactory
{
    protected DbConnection $connection;

    protected DatabaseConfig $dbConfig;

    public function __construct(DbConnection $connection, DatabaseConfig $dbConfig)
    {
        $this->connection = $connection;
        $this->dbConfig = $dbConfig;
    }

    /**
     * Results from this query are parsed by MetadataQueryResultParser
     * @param array|InputTable[] $whitelist
     */
    public function createTablesQuery(array $whitelist): TableMetadataQuery
    {
        // Doc: https://www.ibm.com/support/knowledgecenter/en/SSGU8G_14.1.0/com.ibm.sqlr.doc/ids_sqr_072.htm
        // Note: Each database has own SYSTABLES table, so all returned tables are from current database.
        $sql = [];
        $sql[] = 'SELECT tabid,tabname,tabtype,owner';
        $sql[] = 'FROM SYSTABLES';

        // Only tables and views
        $sql[] = "WHERE tabtype IN ('T', 'E', 'V')";

        // Ignore system tables
        $sql[] = "AND tabname NOT LIKE 'sys%'";

        if ($whitelist) {
            $sql[] = 'AND ' . $this->createWhitelistWhereStmt($whitelist);
        }

        $sql[] = 'ORDER BY tabname';

        return new TableMetadataQuery(
            implode(' ', $sql),
            fn(array $row) => $row['tabid']
        );
    }

    /**
     * Results from this query are parsed by MetadataQueryResultParser
     * Use one of $whitelist or $tablesId for tables whitelisting.
     * @param array|InputTable[] $whitelist tables whitelist specified in configuration
     * @param string[] $tableIds unique IDs of tables loaded in previous step, see MetadataQueryFactory
     */
    public function createColumnsQuery(array $whitelist, array $tableIds): ColumnMetadataQuery
    {
        // Doc: https://www.ibm.com/support/knowledgecenter/en/SSGU8G_14.1.0/com.ibm.sqlr.doc/ids_sqr_025.htm
        // Note: Each database has own SYSCOLUMNS table, so all returned tables/columns are from current database.
        $sql = [];
        $sql[] = 'SELECT tabid,colname,coltype,colno,collength';
        $sql[] = 'FROM SYSCOLUMNS';
        $sql[] = 'WHERE tabid IN (' . $this->createInStmt($tableIds) . ')';
        $sql[] = 'ORDER BY colno';

        return new ColumnMetadataQuery(
            implode(' ', $sql),
            fn(array $row) => $row['tabid'],
            fn(array $row) => $row['tabid'] . '.' . $row['colname']
        );
    }

    /**
     * Results from this query are parsed by MetadataQueryResultParser
     * Use one of $whitelist or $tablesId for tables whitelisting.
     * @param array|InputTable[] $whitelist tables whitelist specified in configuration
     * @param string[] $tableIds unique IDs of tables loaded in previous step, see MetadataQueryFactory
     */
    public function createColumnsConstraintsQuery(array $whitelist, array $tableIds): ColumnMetadataQuery
    {
        // Doc: https://stackoverflow.com/a/10948547
        // Doc: https://www.ibm.com/support/knowledgecenter/SSGU8G_12.1.0/com.ibm.sqlr.doc/ids_sqr_029.htm
        // Note: Each database has own SYSCONSTRAINTS table, so all returned tables/columns are from current database.
        $sql = [];
        $sql[] = 'SELECT st.tabid, st.tabname, sn.constrname, sn.constrtype, sc.colname,';
        $sql[] = 'fk_st.tabname AS reftab, fk_st.owner AS reftabowner, fk_sc.colname as refcolname';
        $sql[] = 'FROM SYSCONSTRAINTS sn';

        // Join column and table name
        $sql[] = 'JOIN SYSTABLES  st ON (sn.tabid = st.tabid AND st.tabid IN (' . $this->createInStmt($tableIds) . '))';
        $sql[] = 'JOIN SYSINDEXES si ON (sn.idxname = si.idxname)';
        $sql[] = 'JOIN SYSCOLUMNS sc ON (sn.tabid = sc.tabid';
        $sql[] = 'AND (';
        for ($i=1; $i<=16; $i++) {
            $sql[] = "sc.colno = si.part$i";
            if ($i !== 16) {
                $sql[] = 'OR';
            }
        }
        $sql[] = '))';

        // Left join foreign key column and table name
        $sql[] = 'LEFT JOIN SYSREFERENCES fk_sr ON (sn.constrid = fk_sr.constrid)';
        $sql[] = 'LEFT JOIN SYSTABLES fk_st ON (fk_sr.ptabid = fk_st.tabid)';
        $sql[] = 'LEFT JOIN SYSCONSTRAINTS fk_sn ON (fk_sr.primary = fk_sn.constrid)';
        $sql[] = 'LEFT JOIN SYSINDEXES fk_si ON (fk_sn.idxname = fk_si.idxname)';
        $sql[] = 'LEFT JOIN SYSCOLUMNS fk_sc ON (fk_si.part1 = fk_sc.colno AND fk_st.tabid = fk_sc.tabid)';

        $sql[] = "WHERE sn.constrtype IN ('P', 'R')";

        return new ColumnMetadataQuery(
            implode(' ', $sql),
            fn(array $row) => $row['tabid'],
            fn(array $row) => $row['tabid'] . '.' . $row['colname']
        );
    }

    protected function createInStmt(array $values): string
    {
        return
            implode(
                ', ',
                array_map(
                    fn($value) => $this->connection->quote((string) $value),
                    $values
                )
            );
    }

    protected function createWhitelistWhereStmt(array $whitelist): string
    {
        $whitelistStmts = array_map(
            fn(InputTable  $table) => sprintf(
                '(tabname = %s AND owner = %s)',
                $this->connection->quote($table->getName()),
                $this->connection->quote($table->getSchema())
            ),
            $whitelist
        );
        return '(' . implode('OR', $whitelistStmts) . ')';
    }
}
