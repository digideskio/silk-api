<?php
namespace OWC\Silk;

use Monolog\Logger,
	Monolog\Handler\StreamHandler;

class Api {

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
			if ( isset( Api::${$key} ) ) {
				Api::${$key} = $value;
			}
		}

		// create a log channel
		Api::$log = new Logger( 'silk' );
		Api::$log->pushHandler( new StreamHandler( dirname( __FILE__ ) . '/../silk.log', Logger::DEBUG ) );
	}

	/*
	|-----------------------------------------------------------
	| GETTERS
	|-----------------------------------------------------------
	*/

	public static function get_url( $append = '/' ) {
		return Api::$url . $append;
	}

	/*
	|-----------------------------------------------------------
	| API CALLS
	|-----------------------------------------------------------
	*/

	// generic
	public static function post( $url, $data = array() )   { return Api::call_api( 'POST', $url, $data ); }
	public static function put( $url, $data = array() )    { return Api::call_api( 'PUT', $url, $data ); }
	public static function get( $url, $data = array() )    { return Api::call_api( 'GET', $url, $data ); }
	public static function delete( $url, $data = array() ) { return Api::call_api( 'DELETE', $url, $data ); }

	// call the Shopify api
	public static function call_api( $method, $url, $raw_data = array() ) {
		$url = Api::get_url( $url );

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
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $raw_data ) );
				break;
		}

		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: application/json', 'API-Authorization: ' . Api::$secret ) );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 ); 
		 
		$response = curl_exec($ch);

		curl_close($ch);

		Api::$log->addInfo(
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
