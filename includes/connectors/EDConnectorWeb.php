<?php
/**
 * Class implementing {{#get_web_data:}} and mw.ext.externalData.getWebData.
 *
 * @author Alexander Mashin
 * @author Yaron Koren
 *
 */
class EDConnectorWeb extends EDConnectorGet {
	/**
	 * Fetch the web data. Sets HTTP headers.
	 *
	 * @return string|null Fetched text.
	 */
	protected function fetcher() {
		// We do not want to repeat error messages self::$tries times.
		static $log_errors = true;
		[ $result, $this->headers, $errors ] = self::request( 'GET', $this->realUrl, $this->options, __METHOD__ );
		if ( $errors && $log_errors ) {
			$this->error( 'externaldata-url-not-fetched', $this->originalUrl );
			foreach ( $errors as $error ) {
				if ( is_array( $error ) && isset( $error['message'] ) && isset( $error['params'] ) ) {
					$this->error( $error['message'], $error['params'] ); // -- MW message.
				} else {
					$this->error( 'externaldata-url-not-fetched', $error ); // -- plain string.
				}
			}
			$log_errors = false; // once is enough.
		}
		return $result;
	}
}
