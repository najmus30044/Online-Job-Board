<?php
/**
 * Job Application Form Template
 *
 * @package JobBoardPlugin
 */

defined( 'ABSPATH' ) || exit;

$job_id = get_the_ID();
?>
<form id="job-application-form" class="job-application-form" enctype="multipart/form-data" method="post" novalidate>
	<input type="hidden" name="action" value="submit_job_application">
	<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>">
	<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'job_application_nonce' ) ); ?>">

	<fieldset>
		<legend class="screen-reader-text">
			<?php esc_html_e( 'Job Application Form', 'job-board-plugin' ); ?>
		</legend>

		<div class="field-row">
			<label for="full_name">
				<?php esc_html_e( 'Full Name', 'job-board-plugin' ); ?> 
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="text" id="full_name" name="full_name" required aria-required="true"
				placeholder="<?php esc_attr_e( 'Your full name', 'job-board-plugin' ); ?>">
		</div>

		<div class="field-row">
			<label for="email">
				<?php esc_html_e( 'Email', 'job-board-plugin' ); ?> 
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="email" id="email" name="email" required aria-required="true"
				placeholder="<?php esc_attr_e( 'Inter Your email', 'job-board-plugin' ); ?>">
		</div>

		<div class="field-row">
			<label for="phone"><?php esc_html_e( 'Phone', 'job-board-plugin' ); ?></label>
			<input type="tel" id="phone" name="phone"
				placeholder="<?php esc_attr_e( 'Inter Your Phone number', 'job-board-plugin' ); ?>"
				pattern="^\+?[0-9\s\-\(\)]{7,20}$"
				title="<?php esc_attr_e( 'Enter a valid phone number', 'job-board-plugin' ); ?>">
		</div>

		<div class="field-row">
			<label for="cover_letter"><?php esc_html_e( 'Cover Letter', 'job-board-plugin' ); ?></label>
			<textarea id="cover_letter" name="cover_letter"
				placeholder="<?php esc_attr_e( 'A short cover letter', 'job-board-plugin' ); ?>"></textarea>
		</div>

		<div class="field-row">
			<label for="resume">
				<?php esc_html_e( 'Resume (PDF only)', 'job-board-plugin' ); ?> 
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="file" id="resume" name="resume" accept="application/pdf" required aria-required="true">
		</div>

		<div class="field-row field-actions">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Apply', 'job-board-plugin' ); ?>
			</button>
		</div>
	</fieldset>
</form>

<div id="application-message" role="status" aria-live="polite"></div>
