<?php
/**
 * Entry point for the admin side of the WP Plugin.
 *
 * @author  Federico Ramirez
 * @since   0.6.0
 */

require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-push.php';
require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-bulk-push.php';
require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'actions/index/class-export.php';
require_once plugin_dir_path( __FILE__ ) . 'class-admin-export-list-table.php';

class Admin_Page_Index extends Apple_Export {

	private $settings;

	function __construct( $settings ) {
		$this->settings = $settings;

		add_action( 'admin_menu', array( $this, 'setup_admin_page' ) );
	}

	public function setup_admin_page() {
		// Set up main page. This page reads parameters and handles actions
		// accordingly.
		add_menu_page(
			'Apple Export',
			'Apple Export',
			'manage_options',
			$this->plugin_name . '_index',
			array( $this, 'page_router' )
		);
	}

	/**
	 * Sets up all pages used in the plugin's admin page. Associate each route
	 * with an action. Actions are methods that end with "_action" and must
	 * perform a task and output HTML with the result.
	 *
	 * FIXME: Regarding this class doing too much, maybe split all actions into
	 * their own class.
	 *
	 * @since 0.4.0
	 */
	public function page_router() {
		$id     = intval( @$_GET['post_id'] );
		$action = htmlentities( @$_GET['action'] );

		// Given an action and ID, map the attributes to corresponding actions.

		if ( ! $id ) {
			switch ( $action ) {
			case 'push':
				return $this->bulk_push_action( $_REQUEST['article'] );
			default:
				return $this->show_post_list_action();
			}
		}

		switch ( $action ) {
		case 'settings':
			return $this->settings_action( $id );
		case 'export':
			return $this->export_action( $id );
		case 'push':
			return $this->push_action( $id );
		case 'delete':
			return $this->delete_action( $id );
		default:
			wp_die( 'Invalid action: ' . $action );
		}
	}

	private function display_message( $title, $message ) {
		include plugin_dir_path( __FILE__ ) . 'partials/page_message.php';
	}

	/**
	 * Gets a setting by name which was loaded from WordPress options.
	 *
	 * @since 0.4.0
	 */
	private function get_setting( $name ) {
		return $this->settings->get( $name );
	}

	/**
	 * Given the full path to a zip file INSIDE THE PLUGN DIRECTORY, redirect
	 * to the appropriate URL to download it.
	 */
	private function download_zipfile( $path ) {
		header( 'Content-Type: application/zip, application/octet-stream' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );

		ob_clean();
		flush();
		readfile( $path );
		exit;
	}

	// Actions
	// -------------------------------------------------------------------------

	private function bulk_push_action( $articles ) {
		$action = new Actions\Index\Bulk_Push( $this->settings, $articles );
		$errors = $action->perform();
		if ( $errors ) {
			$formatted_errors = '<ul>';
			foreach ( $errors as $error ) {
				$formatted_errors .= '<li>' . $error . '</li>';
			}
			$formatted_errors .= '</ul>';
			$this->display_message( 'Oops, something went wrong', $formatted_errors );
		} else {
			$this->display_message( 'Success', 'Your articles has been pushed successfully!' );
		}
	}

	private function show_post_list_action() {
		$table = new Admin_Export_List_Table();
		$table->prepare_items();
		include plugin_dir_path( __FILE__ ) . 'partials/page_index.php';
	}

	private function settings_action( $id ) {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			update_post_meta( $id, 'apple_export_pullquote', $_POST['pullquote'] );
			update_post_meta( $id, 'apple_export_pullquote_position', intval( $_POST['pullquote_position'] ) );
			$message = 'Settings saved.';
		}

		$post      = get_post( $id );
		$post_meta = get_post_meta( $id );
		include plugin_dir_path( __FILE__ ) . 'partials/page_single_settings.php';
	}

	private function export_action( $id ) {
		$action = new Actions\Index\Export( $this->settings, $id );
		$path   = $action->perform();
		//$this->download_zipfile( $path );
		echo $path;
	}

	private function push_action( $id ) {
		$action = new Actions\Index\Push( $this->settings, $id );
		$error  = $action->perform();
		if ( is_null( $error ) ) {
			$this->display_message( 'Success', 'Your article has been pushed successfully!' );
		} else {
			$this->display_message( 'Oops, something went wrong', $error );
		}
	}

	private function delete_action( $id ) {
		$action = new Actions\Index\Delete( $this->settings, $id );
		$error  = $action->perform();
		if ( is_null( $error ) ) {
			$this->display_message( 'Success', 'Your article has been removed from Apple News.' );
		} else {
			$this->display_message( 'Oops, something went wrong', $error );
		}
	}

}
