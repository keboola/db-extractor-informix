# Informix ODBC Extractor

[KBC](https://www.keboola.com/product/) Docker app for extracting data from [IBM Informix](https://www.ibm.com/products/informix) database.

See [Extractors for SQL Databases](https://help.keboola.com/components/extractors/database/sqldb/) for more documentation.

# Usage

## Configuration

The configuration `config.json` contains following properties in `parameters` key: 

*Note:* `query` or `table` must be specified.

*Note:* Parameters `db.serverName`, `db.protocol`, `db.dbLocale` are in addition to other extractors.

- `db` - object (required): Connection settings
    - `host` - string (required): IP address or hostname of Apache Hive DB server
    - **`serverName`** - string (required): Informix database server name, [read more](https://www.querytool.com/help/981.htm).
    - **`protocol`** - enum (optional): protocol `onsoctcp` or `onsocssl`, default `onsoctcp`, [read more](https://www.ibm.com/support/knowledgecenter/en/SSGU8G_11.50.0/com.ibm.admin.doc/ids_admin_0161.htm).
    - **`dbLocale`** - string (optional): Informix `DB_LOCALE`, default `en_US.utf8`.
    - `port` - integer (required): Server port (default port is `10000`)
    - `user` - string (required): User with correct access rights
    - `#password` - string (required): Password for given `user`
    - `database` - string (required): Database to connect to
    - `ssh` - object (optional): Settings for SSH tunnel
        - `enabled` - bool (required):  Enables SSH tunnel
        - `sshHost` - string (required): IP address or hostname of SSH server
        - `sshPort` - integer (optional): SSH server port (default port is `22`)
        - `localPort` - integer (required): SSH tunnel local port in Docker container (default `33006`)
        - `user` - string (optional): SSH user (default same as `db.user`)
        - `compression`  - bool (optional): Enables SSH tunnel compression (default `false`)
        - `keys` - object (optional): SSH keys
            - `public` - string (optional): Public SSH key
            - `#private` - string (optional): Private SSH key
- `query` - string (optional): SQL query whose output will be extracted
- `table` - object (optional): Table whose will be extracted
    - `tableName` - string (required)
    - `schema` - string (required)
- `columns` - array (optional): List of columns to export (default all columns)
- `outputTable` - string (required): Name of the output table 
- `incremental` - bool (optional):  Enables [Incremental Loading](https://help.keboola.com/storage/tables/#incremental-loading)
- `incrementalFetchingColumn` - string (optional): Name of column for [Incremental Fetching](https://help.keboola.com/components/extractors/database/#incremental-fetching)
- `incrementalFetchingLimit` - integer (optional): Max number of rows fetched per one run
- `primaryKey` - string (optional): Sets primary key to specified column in output table
- `retries` - integer (optional): Number of retries if an error occurred

### Examples

Full export:
```json
{
  "parameters": {
    "db": {
      "host": "my-informix.com",
      "serverName": "informix",
      "port": "9088",
      "database": "test",
      "user": "informix",
      "#password": "*****"
    },
    "outputTable": "output",
    "table": {
      "tableName": "simple",
      "schema": "informix"
    }
  }
}
```

Custom query:
```json
{
  "parameters": {
    "db": "...",
    "outputTable": "output",
    "query": "SELECT name, date, id FROM simple",
    "primaryKey": ["id"]
  }
}
```

Incremental fetching + load only defined columns:
```json
{
  "parameters": {
    "db": "...",
    "outputTable": "output",
    "table": {
      "tableName": "incremental",
      "schema": "${DEFAULT_SCHEMA}"
    },
    "columns": ["id", "name", "datetime"],
    "incremental": true,
    "incrementalFetchingColumn": "datetime"
  }
}
```

## Development
 
Create `.env` file with AWS credentials. They are needed to download the driver from the `keboola-drivers` bucket.
```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
```
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/db-extractor-informix
cd db-extractor-informix
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# How to replace the ODBC driver

This repository can be used as a template for any ODBC driver. 
All operations are well covered by unit and functional tests.  
Database-specific code is delimited and defined in one place -
eg. escaping, SQL generation, DB connection, ...

## Steps to replace the ODBC driver

1. **Install a new ODBC driver in the `Dockerfile`**
    - It can be downloaded from the public sources.
    - Or from [a private S3](https://github.com/keboola/db-extractor-informix/blob/f43de64ec06268a3072c1118b18c3583a41e45ea/Dockerfile#L1-L6) if a driver is not publicly available.
    - If you need to use a [CDATA](https://www.cdata.com/) driver, you can be inspired by [db-extractor-netsuite Dockerfile](https://github.com/keboola/db-extractor-netsuite/blob/c80be49f2045ac055c5a7bc2a63dd154f62fa036/Dockerfile#L49-L58)
    - By default, CDATA drivers are licensed for only one computer. 
        - Container always looks like a different computer for the driver.
        - So for CDATA is needed [RTK](https://github.com/keboola/db-extractor-netsuite/blob/c80be49f2045ac055c5a7bc2a63dd154f62fa036/Dockerfile#L3-L5) - runtime license key.
    - Run `docker-compose build` to build modified `Dockerfile`.
2. **Set up a test database in `docker-compose.yml`**
    - Configure  `db` service and modify the environment, eg. `DB_USER`,  `DB_HOST` ...
    - If it is not possible to run the database in a container, then remove the `db` service.
        - And set the environment variables to a test database connection.
        - In this case, `docker-compose.yml` contains only the names of environment variables.
        - The values are defined in the local environment and in the CI.
3. **Modify `OdbcDsnFactory` class to create the correct DSN connection string**
    - You may need to modify the configuration definition `OdbcDbNode` and `OdbcDatabaseConfig` classes.
    - In these classes: 
        - Remove configuration nodes that are not needed.
        - Add the new nodes needed to configure the ODBC driver.
        - Don't forget to write these changes into `README.md`.
4. **Prepare test environment**
    - Modify `getDbConfigArray` method in `OdbcTestConnectionFactory` to match `OdbcDbNode` and `docker-compose.yml`.
    - Modify SQL in `RemoveAllTablesTrait::removeAllTables`.
5. **Test connection**
    - Everything should be ready for the driver to connect to the database.
    - Run `docker-compose run --rm dev ./vendor/bin/phpunit ./tests/phpunit/OdbcDriverTest.php`
6. **Modify the database-specific code in the extractor**
    - Go through these classes and modify them if needed:
        - `QuoteIdentifierTrait` and `QuoteTrait`
        - `OdbcConnection::testConnection` and `OdbcQueryFactory`
        - `MetadataQueryFactory` and `OdbcMetadataProcessor`
        - `OdbcManifestSerializer` and `OdbcDataType`
7. **Modify the database-specific code in the tests**
    - Go through these classes and modify them if needed:
        - `CreateTableTrait`, `DefaultSchemaTrait`, `InsertRowsTrait`
        - `ComplexTableTrait`, `EscapingTableTrait`, `IncrementalTableTrait`, `PkAndFkTablesTrait`, `SimpleTableTrait`
8. **Run all tests**
    - Run `docker-compose run --rm dev composer ci` a and fix errors found.
9. **Setup continuous integration**
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
