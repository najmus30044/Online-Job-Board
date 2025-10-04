<?php
/**
 * Template Name: Single Job Listing
 *
 * @package JobBoardPlugin
 */

get_header();
?>

<div class="single-job-container">
	<?php
	while ( have_posts() ) :
		the_post();

		$job_id         = get_the_ID();
		$company_name   = get_post_meta( $job_id, '_company_name', true );
		$company_address = get_post_meta( $job_id, '_company_address', true );
		$deadline       = get_post_meta( $job_id, '_application_deadline', true );
		$start          = get_post_meta( $job_id, '_application_start', true );
		$today          = strtotime( current_time( 'Y-m-d' ) ); // Use WP current_time().
		$is_closed      = $deadline && strtotime( $deadline ) < $today;
		$not_started    = $start && strtotime( $start ) > $today;

		$status_label = '';
		if ( $not_started ) {
			$status_label = ' <span class="job-not-started">( ' . esc_html__( 'Not yet open', 'job-board-plugin' ) . ' )</span>';
		} elseif ( $is_closed ) {
			$status_label = ' <span class="job-closed">( ' . esc_html__( 'Closed', 'job-board-plugin' ) . ' )</span>';
		}
		?>

		<header class="job-header">
			<h1 class="job-title">
				<?php the_title(); ?>
				<?php echo wp_kses_post( $status_label ); ?>
			</h1>

			<div class="job-meta">
				<?php if ( $company_name ) : ?>
					<span class="job-company">
						<?php esc_html_e( 'Company:', 'job-board-plugin' ); ?>
						<?php echo esc_html( $company_name ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $company_address ) : ?>
					<span class="job-address">
						<?php esc_html_e( 'Location:', 'job-board-plugin' ); ?>
						<?php echo esc_html( $company_address ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $deadline ) : ?>
					<span class="job-deadline">
						<?php esc_html_e( 'Application Deadline:', 'job-board-plugin' ); ?>
						<?php echo esc_html( $deadline ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $start ) : ?>
					<span class="job-start">
						<?php esc_html_e( 'Application Start:', 'job-board-plugin' ); ?>
						<?php echo esc_html( $start ); ?>
					</span>
				<?php endif; ?>
			</div>
		</header>

		<div class="job-content">
			<div class="job-grid">
				<main class="job-main" role="main">
					<div class="job-description">
						<?php the_content(); ?>
					</div>
				</main>

				<aside class="job-sidebar">
					<?php if ( $not_started ) : ?>
						<div class="job-not-started-message panel">
							<p><?php esc_html_e( 'This job posting is not yet open. Please check back on the start date.', 'job-board-plugin' ); ?></p>
						</div>
					<?php elseif ( $is_closed ) : ?>
						<div class="job-closed-message panel">
							<p><?php esc_html_e( 'This job posting is closed.', 'job-board-plugin' ); ?></p>
						</div>
					<?php else : ?>
						<div class="job-application-section panel">
							<h2 class="application-title"><?php esc_html_e( 'Apply for This Job', 'job-board-plugin' ); ?></h2>
							<?php
							//include template.
							$template_path = JOB_BOARD_PLUGIN_DIR . 'includes/templates/job-application-form.php';
							if ( file_exists( $template_path ) ) {
								include $template_path;
							}
							?>
						</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
