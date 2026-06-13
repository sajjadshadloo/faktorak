<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Faktorak_Plugin_Manager_Client {
    private const OPTION_LAST_SENT     = 'faktorak_plugin_manager_last_sent';
    private const OPTION_LAST_ENDPOINT = 'faktorak_plugin_manager_last_endpoint';

    public function __construct() {
        add_action( 'admin_init', array( $this, 'maybe_send_ping' ) );
    }

    private function manager_url() {
        $url = defined( 'FAKTORAK_PLUGIN_MANAGER_URL' ) ? (string) FAKTORAK_PLUGIN_MANAGER_URL : '';
        return untrailingslashit( $url );
    }

    private function manager_token() {
        return defined( 'FAKTORAK_PLUGIN_MANAGER_TOKEN' ) ? (string) FAKTORAK_PLUGIN_MANAGER_TOKEN : '';
    }

    private function endpoint() {
        return $this->manager_url() . '/wp-json/sjd-plugin-manager/v1/install';
    }

    private function can_send() {
        return is_admin()
            && ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) )
            && '' !== $this->manager_url()
            && '' !== $this->manager_token();
    }

    public function maybe_send_ping() {
        if ( ! $this->can_send() ) {
            return;
        }

        $endpoint      = $this->endpoint();
        $last_endpoint = (string) get_option( self::OPTION_LAST_ENDPOINT, '' );
        $last_sent     = (int) get_option( self::OPTION_LAST_SENT, 0 );

        if ( $endpoint === $last_endpoint && time() - $last_sent < DAY_IN_SECONDS ) {
            return;
        }

        update_option( self::OPTION_LAST_SENT, time(), false );
        update_option( self::OPTION_LAST_ENDPOINT, $endpoint, false );

        wp_remote_post(
            $endpoint,
            array(
                'blocking'  => false,
                'timeout'   => 2,
                'sslverify' => true,
                'headers'   => array(
                    'Content-Type'               => 'application/json',
                    'X-SJD-Plugin-Manager-Token' => $this->manager_token(),
                ),
                'body'      => wp_json_encode( $this->payload() ),
            )
        );
    }

    private function payload() {
        $site_url = untrailingslashit( home_url() );
        $domain   = wp_parse_url( $site_url, PHP_URL_HOST );
        $domain   = preg_replace( '/^www\./', '', strtolower( (string) $domain ) );

        return array(
            'plugin_key'     => 'faktorak',
            'plugin_label'   => 'فاکتورک',
            'plugin_version' => defined( 'FAKTORAK_VERSION' ) ? FAKTORAK_VERSION : '',
            'site_url'       => $site_url,
            'domain'         => $domain,
        );
    }
}
