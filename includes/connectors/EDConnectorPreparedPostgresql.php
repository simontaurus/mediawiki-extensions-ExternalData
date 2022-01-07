<?php
/**
 * Class implementing {{#get_db_data:}} and mw.ext.externalData.getDbData
 * for database connections to PostgreSQL servers with prepared statements.
 *
 * @author Alexander Mashin
 *
 */
class EDConnectorPreparedPostgresql extends EDConnectorPrepared {
	/** @var string $connectionString PostgreSQL connection string. */
	private $connectionString;
	/** @var PgSql\Connection $pg Connection to PostrgreSQL server. */
	private $pg;
	/** @var mysqli_stmt $prepared The prepared query. */
	protected $prepared;

	/**
	 * Constructor. Analyse parameters and wiki settings; set $this->errors.
	 *
	 * @param array &$args Arguments to parser or Lua function; processed by this constructor.
	 * @param Title $title A Title object.
	 */
	protected function __construct( array &$args, Title $title ) {
		parent::__construct( $args, $title );
		// Make connection string.
		$str = '';
		foreach ( $this->credentials as $name => $value ) {
			$str .= "$name='" . str_replace( "'", "\\'", $value ) . "' ";
		}
		$this->connectionString = $str;
	}

	/**
	 * Establish connection the database server.
	 * @return bool
	 */
	protected function connect() {
		// Throw exceptions instead of warnings.
		self::throwWarnings();
		try {
			$this->pg = pg_connect( $this->connectionString, PGSQL_CONNECT_FORCE_NEW );
		} catch ( Exception $e ) {
			$this->error( 'externaldata-db-could-not-connect', $e->getMessage() );
			self::stopThrowingWarnings();
			return false;
		}
		self::stopThrowingWarnings();
		if ( $this->pg === false || pg_connection_status( $this->pg ) !== PGSQL_CONNECTION_OK ) {
			// Could not create Database object.
			$this->error( 'externaldata-db-could-not-connect', '(no connection)' );
			return false;
		}
		return true;
	}

	/**
	 * Get query result as a two-dimensional array.
	 * @return string[][]|void
	 */
	protected function fetch() {
		// Prepared statement.
		$this->prepared = pg_prepare( $this->pg, $this->name, $this->query );
		if ( $this->prepared === false ) {
			$this->error( 'externaldata-db-invalid-query', $this->query );
		}

		// Execute query.
		$result = pg_execute( $this->pg, $this->name, $this->parameters );
		if ( $result !== false ) {
			$rows = pg_fetch_all( $result, PGSQL_ASSOC );
			return $rows;
		} else {
			$this->error( 'externaldata-db-no-return-values' );
		}
	}

	/**
	 * Disconnect from DB server.
	 */
	protected function disconnect() {
		pg_close( $this->pg );
	}
}
