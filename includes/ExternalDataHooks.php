<?php
/**
 * Hook functions for the External Data extension.
 *
 * @file
 * @ingroup ExternalData
 * @author Yaron Koren
 */
class ExternalDataHooks {

	/**
	 * @param Parser &$parser
	 * @return bool
	 */
	public static function registerParser( Parser &$parser ) {
		$parser->setFunctionHook( 'get_web_data', [ 'EDParserFunctions', 'getWebData' ] );
		$parser->setFunctionHook( 'get_file_data', [ 'EDParserFunctions', 'getFileData' ] );
		$parser->setFunctionHook( 'get_soap_data', [ 'EDParserFunctions', 'getSOAPData' ] );
		$parser->setFunctionHook( 'get_ldap_data', [ 'EDParserFunctions', 'getLDAPData' ] );
		$parser->setFunctionHook( 'get_db_data', [ 'EDParserFunctions', 'getDBData' ] );
		$parser->setFunctionHook( 'get_program_data', [ 'EDParserFunctions', 'getProgramData' ] );
		$parser->setFunctionHook( 'get_external_data', [ 'EDParserFunctions', 'getExternalData' ] );

		$parser->setFunctionHook( 'external_value', [ 'EDParserFunctions', 'doExternalValue' ] );
		$parser->setFunctionHook( 'for_external_table', [ 'EDParserFunctions', 'doForExternalTable' ] );
		$parser->setFunctionHook( 'display_external_table', [ 'EDParserFunctions', 'doDisplayExternalTable' ] );
		$parser->setFunctionHook( 'store_external_table', [ 'EDParserFunctions', 'doStoreExternalTable' ] );
		$parser->setFunctionHook( 'clear_external_data', [ 'EDParserFunctions', 'doClearExternalData' ] );

		EDConnectorExe::registerTags( $parser );

		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * @param string $engine
	 * @param array &$extraLibraries
	 * @return bool
	 */
	public static function registerLua( $engine, array &$extraLibraries ) {
		$class = 'EDScribunto';
		// Autoload class here and not in extension.json, so that it is not loaded if Scribunto is not enabled.
		global $wgAutoloadClasses;
		$wgAutoloadClasses[$class] = __DIR__ . '/' . $class . '.php';
		$extraLibraries['mw.ext.externaldata'] = $class;
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * Register used software for Special:Version.
	 *
	 * @param array &$software
	 */
	public static function onSoftwareInfo( array &$software ) {
		EDConnectorExe::addSoftware( $software );
	}

	/**
	 * Form extension configuration from different sources.
	 */
	public static function onRegistration() {
		// Load configuration settings.
		EDConnectorBase::loadConfig();
	}

	/**
	 * For update.php. See also includes/connectors/traits/EDConnectorCached.php.
	 *
	 * @param DatabaseUpdater $updater
	 * @return void
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		// Create ed_url_cache table. The obsolete setting $edgCacheTable is ignored.
		$updater->addExtensionTable( 'ed_url_cache', __DIR__ . '/../sql/ExternalData.sql' );
	}
}
