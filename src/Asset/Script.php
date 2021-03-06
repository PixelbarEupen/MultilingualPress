<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Asset;

/**
 * Interface for all script data type implementations.
 *
 * @package Inpsyde\MultilingualPress\Asset
 * @since   3.0.0
 */
interface Script extends Asset {

	/**
	 * Makes the given data available for the script.
	 *
	 * @since 3.0.0
	 *
	 * @param string $object_name The name of the JavaScript variable holding the data.
	 * @param array  $data        The data to be made available for the script.
	 *
	 * @return Script Script instance.
	 */
	public function add_data( string $object_name, array $data ): Script;

	/**
	 * Clears the data so it won't be output another time.
	 *
	 * @since 3.0.0
	 *
	 * @return Script Script instance.
	 */
	public function clear_data(): Script;

	/**
	 * Returns all data to be made available for the script.
	 *
	 * @since 3.0.0
	 *
	 * @return array[] Data to be made available for the script.
	 */
	public function data(): array;
}
