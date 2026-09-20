<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wdta_settings' );

$wdta_up  = wp_upload_dir( null, false );
$wdta_dir = trailingslashit( $wdta_up['basedir'] ) . 'wd-turkiye-adres/';

if ( is_dir( $wdta_dir ) ) {
	$wdta_it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $wdta_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $wdta_it as $wdta_f ) {
		$wdta_f->isDir() ? @rmdir( $wdta_f->getPathname() ) : @unlink( $wdta_f->getPathname() ); // phpcs:ignore
	}
	@rmdir( $wdta_dir ); // phpcs:ignore
}
