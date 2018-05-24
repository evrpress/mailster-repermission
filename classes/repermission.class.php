<?php

class MailsterRePermission {

	private $plugin_path;
	private $plugin_url;

	public function __construct() {

		$this->plugin_path = plugin_dir_path( MAILSTER_REPERMISSION_FILE );
		$this->plugin_url = plugin_dir_url( MAILSTER_REPERMISSION_FILE );

		register_activation_hook( MAILSTER_REPERMISSION_FILE, array( &$this, 'activate' ) );

		load_plugin_textdomain( 'mailster-repermission' );

		add_action( 'init', array( &$this, 'init' ), 1 );
	}

	public function init() {

		if ( ! function_exists( 'mailster' ) ) {

			add_action( 'admin_notices', array( &$this, 'notice' ) );
			return;

		}

		add_filter( 'mailster_setting_sections', array( &$this, 'settings_tab' ), 1 );
		add_action( 'mailster_section_tab_repermission',array( &$this, 'settings' ) );
		add_action( 'mailster_click',array( &$this, 'click' ), 10, 4 );
		add_action( 'mailster_export_fields',array( &$this, 'add_export_field' ) );
		add_action( 'mailster_export_heading__gdpr',array( &$this, 'export_heading' ), 10, 2 );
		add_action( 'mailster_export_field__gdpr',array( &$this, 'export_field' ), 10, 2 );
		add_action( 'mailster_export_args',array( &$this, 'export_args' ), 10, 2 );

	}

	public function settings_tab( $settings ) {

		$position = 4;
		$settings = array_slice( $settings, 0, $position, true ) +
					array( 'repermission' => 'Re-Permission' ) +
					array_slice( $settings, $position, null, true );

		return $settings;
	}


	public function settings() {

		include $this->plugin_path . '/views/settings.php';

	}

	public function click( $subscriber_id, $campaign_id, $target, $index ) {

		if ( ! $subscriber_id ) {
			return;
		}

		$repermission_ids = array_map( 'trim', explode( ',' , mailster_option( 'repermission_id' ) ) );

		if ( ! in_array( $campaign_id, $repermission_ids ) ) {
			return;
		}

		if ( ! $target ) {
			return;
		}
		if ( $target == mailster_option( 'repermission_link' ) ) {
			if ( ! ( $field = mailster_option( 'repermission_field' ) ) ) {
				return;
			}
			mailster( 'subscribers' )->add_custom_value( $subscriber_id, $field, true );
		} elseif ( $target == mailster_option( 'repermission_unlink' ) ) {
			mailster( 'subscribers' )->unsubscribe( $subscriber_id, $campaign_id, __( 'Didn\'t give consent on the RePermission campaign', 'mailster-repermission' ) );
		}

	}

	public function export_heading( $value, $options ) {

		return __( 'GDPR confirmation time', 'mailster-repermission' );
	}

	public function export_field( $value, $options ) {

		if ( $value ) {
			$timeoffset = mailster( 'helper' )->gmt_offset( true );
			$format = $options['dateformat'] ? $options['dateformat'] : 'U';
			return mailster( 'helper' )->do_timestamp( $value + $timeoffset, $format );
		}

		return __( 'not confirmed!', 'mailster-repermission' );
	}

	public function add_export_field( $fields ) {

		$fields['_gdpr'] = __( 'GDPR confirmation time', 'mailster-repermission' );

		return $fields;
	}

	public function export_args( $args, $options ) {
		global $wpdb;

		if ( isset( $options['column'] ) && false !== array_search( '_gdpr', $options['column'] ) ) {
			$repermission_ids = array_map( 'trim', explode( ',' , mailster_option( 'repermission_id' ) ) );

			$args['select'][] = 'subscribers.*';
			$args['select'][] = 'actions_gdpr.timestamp AS _gdpr';
			$args['join'][] = "LEFT JOIN {$wpdb->prefix}mailster_actions AS actions_gdpr ON actions_gdpr.type = 3 AND subscribers.ID = actions_gdpr.subscriber_id AND actions_gdpr.campaign_id IN (" . implode( ',', $repermission_ids ) . ')';
			$args['join'][] = "LEFT JOIN {$wpdb->prefix}mailster_links AS actions_gdpr_link ON actions_gdpr_link.ID = actions_gdpr.link_id";
			$args['where'][] = "actions_gdpr_link.link = '" . mailster_option( 'repermission_link' ) . "'";
		}

		return $args;
	}

	public function notice() {
		$msg = sprintf( __( 'You have to enable the %s to use the Re-Permission Add On!', 'mailster-repermission' ), '<a href="https://mailster.co/?utm_campaign=wporg&utm_source=Re-Permission+for+Mailster">Mailster Newsletter Plugin</a>' );
	?>
		<div class="error"><p><strong><?php	echo $msg; ?></strong></p></div>
	<?php

	}

	public function activate() {

		if ( function_exists( 'mailster' ) ) {

		}

	}

}
