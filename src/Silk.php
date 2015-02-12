<?php
namespace OWC\Silk;

use Monolog\Logger,
	Monolog\Handler\StreamHandler;

class Silk {

	/*
	|-----------------------------------------------------------
	| PROPERTIES
	|-----------------------------------------------------------
	*/

	// Shopify api domain
	private static $url = 'http://silk.oakwood.se/api/shop';

	// Api key for the private app on your store
	private static $secret = '';

	// Logger
	private static $log = null;

	/*
	|-----------------------------------------------------------
	| CONSTRUCTOR
	|-----------------------------------------------------------
	*/

	public function __construct( $props ) {
		foreach ( $props as $key => $value ) {
			if ( isset( Silk::${$key} ) ) {
				Silk::${$key} = $value;
			}
		}

		// create a log channel
		Silk::$log = new Logger( 'silk' );
		Silk::$log->pushHandler( new StreamHandler( dirname( __FILE__ ) . '/../silk.log', Logger::DEBUG ) );
	}

	/*
	|-----------------------------------------------------------
	| GETTERS
	|-----------------------------------------------------------
	*/

	public static function get_url( $append = '/' ) {
		return Silk::$url . $append;
	}

	/*
	|-----------------------------------------------------------
	| API CALLS
	|-----------------------------------------------------------
	*/

	// generic
	public static function post( $url, $data = array() )   { return Silk::call_api( 'POST', $url, $data ); }
	public static function put( $url, $data = array() )    { return Silk::call_api( 'PUT', $url, $data ); }
	public static function get( $url, $data = array() )    { return Silk::call_api( 'GET', $url, $data ); }
	public static function delete( $url, $data = array() ) { return Silk::call_api( 'DELETE', $url, $data ); }

	// call the Shopify api
	public static function call_api( $method, $url, $raw_data = array() ) {
		$url = Silk::get_url( $url );

		$data = http_build_query( $raw_data );
		 
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		
		switch ( $method ) {
			case 'GET':
				$url .= '?' . $data;
				break;

			case 'DELETE':
			case 'PUT':
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
				break;
			
			case 'POST':
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
				break;
		}

		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: application/json', 'API-Authorization: ' . Silk::$secret ) );
		curl_setopt( $ch, CURLOPT_URL, $url );
		 
		$response = curl_exec($ch);

		curl_close($ch);

		Silk::$log->addInfo(
			'Silk',
			compact( 'url', 'method', 'data', 'response' )
		);

		$response = json_decode( $response );

		if ( $response ) {
			return $response;
		}

		return false;
	}

}
