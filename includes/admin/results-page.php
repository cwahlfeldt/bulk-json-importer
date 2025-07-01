<?php
/**
 * Results page template
 *
 * @package Bulk_JSON_Importer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Bulk Post Importer - Import Results', 'bulk-json-importer' ); ?></h1>

	<div class="notice notice-success is-dismissible">
		<p>
			<?php
			printf(
				esc_html__( 'Import process completed for file %1$s in %2$s seconds. %3$d posts imported successfully.', 'bulk-json-importer' ),
				'<strong>' . esc_html( $result['original_file_name'] ) . '</strong>',
				esc_html( $result['duration'] ),
				absint( $result['imported_count'] )
			);
			?>
		</p>
	</div>

	<?php if ( $result['skipped_count'] > 0 ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					esc_html( _n( '%d item was skipped.', '%d items were skipped.', $result['skipped_count'], 'bulk-json-importer' ) ),
					absint( $result['skipped_count'] )
				);
				?>
				<?php if ( ! empty( $result['error_messages'] ) ) : ?>
					<br><?php esc_html_e( 'See details below.', 'bulk-json-importer' ); ?>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $result['error_messages'] ) ) : ?>
		<h2><?php esc_html_e( 'Import Log (Errors & Notices)', 'bulk-json-importer' ); ?></h2>
		<div style="border: 1px solid #ccd0d4; background: #fff; padding: 10px 15px; max-height: 400px; overflow-y: auto;">
			<ul style="list-style: disc; margin-left: 20px;">
				<?php foreach ( $result['error_messages'] as $message ) : ?>
					<li><?php echo wp_kses_post( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<p style="margin-top: 20px;">
		<a href="<?php echo esc_url( admin_url( 'tools.php?page=' . BJI_PLUGIN_SLUG ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Import Another File', 'bulk-json-importer' ); ?>
		</a>
	</p>
</div>