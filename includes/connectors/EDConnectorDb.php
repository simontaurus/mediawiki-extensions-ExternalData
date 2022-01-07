<?php
/**
 * Base abstract class implementing {{#get_db_data:}} and mw.ext.externalData.getDbData.
 *
 * @author Yaron Koren
 * @author Alexander Mashin
 *
 */
abstract class EDConnectorDb extends EDConnectorBase {
	/** @var string Database ID. */
	protected $dbId;	// Database ID.

	/** @var string Database type. */
	protected $type;
	/** @var array Connection settings. */
	protected $credentials = [];

	// SQL query components.
	/** @var array Columns to query. */
	protected $columns;

	/**
	 * Constructor. Analyse parameters and wiki settings; set $this->errors.
	 *
	 * @param array &$args Arguments to parser or Lua function; processed by this constructor.
	 * @param Title $title A Title object.
	 */
	protected function __construct( array &$args, Title $title ) {
		parent::__construct( $args, $title );

		// Specific parameters.
		if ( isset( $args['db'] ) ) {
			$this->dbId = $args['db'];
		} elseif ( isset( $args['server'] ) ) {
			// For backwards-compatibility - 'db' parameter was
			// added in External Data version 1.3.
			$this->dbId = $args['server'];
		}
		if ( !$this->dbId ) {
			$this->error( 'externaldata-no-param-specified', 'db' );
		}
		if ( isset( $args['type'] ) ) {
			$this->type = $args['type'];
		} else {
			$this->error( 'externaldata-db-incomplete-information', $this->dbId, 'type' );
		}
		// Database credentials.
		$this->setCredentials( $args );	// late binding.
		// Query parts.
		$this->columns = array_values( $this->mappings );
	}

	/**
	 * Set credentials settings for database from $this->dbId.
	 * Should be overloaded, with a call to parent::setCredentials().
	 *
	 * @param array $params Supplemented parameters.
	 */
	protected function setCredentials( array $params ) {
		$this->credentials['user'] = isset( $params['user' ] ) ? $params['user' ] : null;
		$this->credentials['password'] = isset( $params['password' ] ) ? $params['password' ] : null;
		if ( isset( $params[ 'name' ] ) ) {
			$this->credentials['dbname'] = $params['name'];
		} else {
			$this->error( 'externaldata-db-incomplete-information', $this->dbId, 'name' );
		}
	}

	/**
	 * Actually connect to the external data source.
	 * It is presumed that there are no errors in parameters and wiki settings.
	 * Set $this->values and $this->errors.
	 *
	 * @return bool True on success, false if error were encountered.
	 */
	public function run() {
		if ( !$this->connect() /* late binding. */ ) {
			return false;
		}
		$rows = $this->fetch(); // late binding.
		if ( !$rows ) {
			return false;
		}
		$this->add( $this->processRows( $rows ) );
		// $this->values = $this->processRows( $rows ); // late binding.
		$this->disconnect(); // late binding.
		return true;
	}

	/**
	 * Establish connection the database server.
	 */
	abstract protected function connect();

	/**
	 * Get query text.
	 * @return string
	 */
	abstract protected function getQuery();

	/**
	 * Get query result as a two-dimensional array.
	 * @return mixed
	 */
	abstract protected function fetch();

	/**
	 * Postprocess query result.
	 * @param mixed $rows A two-dimensional array or result wrapper containing query results.
	 * @param array $aliases An optional associative array of column aliases.
	 * @return array A two-dimensional array containing post-processed query results
	 */
	protected function processRows( $rows, array $aliases = [] ): array {
		$result = [];
		foreach ( $rows as $row ) {
			foreach ( $this->columns as $column ) {
				$alias = isset( $aliases[$column] ) ? $aliases[$column] : $column;
				if ( !isset( $result[$column] ) ) {
					$result[$column] = [];
				}
				// Can be both array and object.
				$result[$column][] = self::processField( is_array( $row ) ? $row[$alias] : $row->$alias );
			}
		}
		return $result;
	}

	/**
	 * Process field value.
	 *
	 * @param string|DateTime $value
	 * @return string
	 */
	protected static function processField( $value ) {
		// This can happen with MSSQL.
		if ( $value instanceof DateTime ) {
			$value = $value->format( 'Y-m-d H:i:s' );
		}
		// Convert the encoding to UTF-8
		// if necessary - based on code at
		// http://www.php.net/manual/en/function.mb-detect-encoding.php#102510
		return mb_detect_encoding( $value, 'UTF-8', true ) === 'UTF-8'
			? $value
			: utf8_encode( $value );
	}

	/**
	 * Disconnect from DB server.
	 */
	abstract protected function disconnect();
}
