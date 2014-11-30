<?php

/**
 * Logs WordPress core updates
 */
class SimpleCoreUpdatesLogger extends SimpleLogger
{

	public $slug = __CLASS__;

	public function loaded() {
		
		add_action( '_core_updated_successfully', array( $this, "on_core_updated" ) );

	}

	/**
	 * Get array with information about this logger
	 * 
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(			
			"name" => "Core Updates Logger",
			"search_label" => "WordPress (core updates)",
			"description" => "Logs the update of WordPress (manual and automatic updates)",
			"capability" => "update_core",
			"messages" => array(
				'core_updated' => __('Updated WordPress from {prev_version} to {new_version}', 'simple-history'),
				'core_auto_updated' => __('WordPress auto-updated from {prev_version} to {new_version}', 'simple-history')
			)
		);
		
		return $arr_info;

	}

	/**
	 * Called when WordPress is updated
	 *
	 * @param string $new_wp_version
	 */
	public function on_core_updated($new_wp_version) {
		
		$old_wp_version = $GLOBALS['wp_version'];

		$auto_update = true;		
		if ( $GLOBALS['pagenow'] == 'update-core.php' ) {
			$auto_update = false;
		}

		if ($auto_update) {
			$message = "core_auto_updated";
		} else {
			$message = "core_updated";
		}

		$this->noticeMessage(
			$message,
			array(
				"prev_version" => $old_wp_version,
				"new_version" => $new_wp_version
			)
		);

	}

}