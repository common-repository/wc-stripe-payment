<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @property int    $fee
 * @property int    $net
 * @property string $currency
 */
class WPP_Payment_Balance {

	private $data = array();

	private $order;

	/**
	 * @param WC_Order $order
	 */
	public function __construct( $order ) {
		$this->order = $order;
		$this->data  = array(
			'currency' => $order->get_meta( '_stripe_currency' ),
			'fee'      => $order->get_meta( '_stripe_fee' ),
			'net'      => $order->get_meta( '_stripe_net' )
		);
	}

	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	public function __set( $name, $value ) {
		$this->set_prop( $name, $value );
	}

	public function __get( $name ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			return $this->{'get_' . $name}();
		}

		return $this->get_prop( $name );
	}

	private function set_prop( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	private function get_prop( $key, $default = '' ) {
		if ( ! isset( $this->data[ $key ] ) ) {
			$this->data[ $key ] = $default;
		}

		return $this->data[ $key ];
	}

	/**
	 * @return mixed
	 */
	public function get_fee() {
		return $this->get_prop( 'fee', 0 );
	}

	/**
	 * @return mixed
	 */
	public function get_net() {
		return $this->get_prop( 'net', 0 );
	}

	/**
	 * @return mixed
	 */
	public function get_currency() {
		return $this->get_prop( 'currency' );
	}

	public function to_array() {
		return $this->data;
	}

	public function update_meta_data( $save = false ) {
		if ( $this->order ) {
			$this->order->update_meta_data( '_stripe_currency', $this->currency );
			$this->order->update_meta_data( '_stripe_fee', $this->fee );
			$this->order->update_meta_data( '_stripe_net', $this->net );
			if ( $save ) {
				$this->order->save();
			}
		}
	}

}