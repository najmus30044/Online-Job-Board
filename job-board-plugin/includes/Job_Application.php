<?php
/**
 * Job Application Class
 *
 * Handles job application submissions, database storage, and admin UI.
 *
 * @package JobBoardPlugin
 */

namespace JobBoardPlugin;

defined( 'ABSPATH' ) || exit;

use JobBoardPlugin\Traits\Singleton;

/**
 * Class Job_Application
 */
class Job_Application {
	use Singleton;

	/**
	 * Create applications table if not exists.
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'job_applications';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id bigint(20) UNSIGNED NOT NULL,
			full_name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			cover_letter text DEFAULT NULL,
			resume_url varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// If the table already existed with a zero-date default for created_at
		$column = $wpdb->get_row( "SHOW COLUMNS FROM {$table_name} LIKE 'created_at'" );
		if ( $column ) {
			$default = isset( $column->Default ) ? $column->Default : null;
			if ( null === $default || '0000-00-00 00:00:00' === $default ) {
				$wpdb->query( "ALTER TABLE {$table_name} MODIFY created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP" );
			}
			$wpdb->query( "UPDATE {$table_name} SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL" );
		}
	}

	/**
	 * Constructor.
	 */
	final private function __construct() {
		// Ajax handlers.
		add_action( 'wp_ajax_submit_job_application', [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_submit_job_application', [ $this, 'handle_submission' ] );

		// Admin AJAX handlers for managing applications (edit/delete/get)
		add_action( 'wp_ajax_job_app_get', [ $this, 'ajax_get_application' ] );
		add_action( 'wp_ajax_job_app_update', [ $this, 'ajax_update_application' ] );
		add_action( 'wp_ajax_job_app_delete', [ $this, 'ajax_delete_application' ] );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Admin menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

	/**
	 * Handle job application submission via Ajax.
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		check_ajax_referer( 'job_application_nonce', 'nonce' );

		$job_id       = isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0;
		$full_name    = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$cover_letter = isset( $_POST['cover_letter'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cover_letter'] ) ) : '';

		if ( empty( $job_id ) || empty( $full_name ) || empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid data.', 'job-board-plugin' ) );
		}

		global $wpdb;
		$table_name  = esc_sql( $wpdb->prefix . 'job_applications' );
		$email_clean = strtolower( trim( $email ) );

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table_name}` WHERE job_id = %d AND email = %s",
				$job_id,
				$email_clean
			)
		);

		if ( $exists && intval( $exists ) > 0 ) {
			wp_send_json_error( __( 'You have already applied for this job with the same email.', 'job-board-plugin' ) );
		}

		// Resume upload validation.
		if ( ! isset( $_FILES['resume'] ) || empty( $_FILES['resume']['name'] ) ) {
			wp_send_json_error( __( 'Please attach your resume (PDF).', 'job-board-plugin' ) );
		}

		$file = $_FILES['resume'];
		if ( ! isset( $file['error'] ) || 0 !== $file['error'] ) {
			wp_send_json_error( __( 'There was a problem with the uploaded resume.', 'job-board-plugin' ) );
		}

		$is_pdf = false;
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( $finfo ) {
				$mime = finfo_file( $finfo, $file['tmp_name'] );
				finfo_close( $finfo );
				if ( 'application/pdf' === $mime ) {
					$is_pdf = true;
				}
			}
		}

		if ( ! $is_pdf ) {
			$provided_type = isset( $file['type'] ) ? $file['type'] : '';
			$name          = isset( $file['name'] ) ? $file['name'] : '';
			if ( 'application/pdf' === $provided_type || 'pdf' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				$is_pdf = true;
			}
		}

		if ( ! $is_pdf ) {
			wp_send_json_error( __( 'Resume must be a PDF file.', 'job-board-plugin' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'resume', $job_id );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( __( 'Upload failed.', 'job-board-plugin' ) );
		}

		$resume_url = wp_get_attachment_url( $attachment_id );

		// Insert data.
		$inserted = $wpdb->insert(
			$table_name,
			[
				'job_id'       => $job_id,
				'full_name'    => $full_name,
				'email'        => $email_clean,
				'phone'        => $phone,
				'cover_letter' => $cover_letter,
				'resume_url'   => $resume_url,
				'created_at'   => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			$db_error = isset( $wpdb->last_error ) ? $wpdb->last_error : '';

			if (
				stripos( $db_error, 'doesn\'t exist' ) !== false ||
				stripos( $db_error, 'does not exist' ) !== false ||
				stripos( $db_error, 'no such table' ) !== false
			) {
				self::create_table();

				$inserted = $wpdb->insert(
					$table_name,
					[
						'job_id'       => $job_id,
						'full_name'    => $full_name,
						'email'        => $email_clean,
						'phone'        => $phone,
						'cover_letter' => $cover_letter,
						'resume_url'   => $resume_url,
					],
					[ '%d', '%s', '%s', '%s', '%s', '%s' ]
				);
			}

			if ( ! $inserted ) {
				if ( ! empty( $db_error ) ) {
					error_log( '[job-board-plugin] DB insert failed: ' . $db_error ); 
				}
				wp_send_json_error( __( 'Database error.', 'job-board-plugin' ) );
			}
		}

		// Email notifications.
		$job_title   = get_the_title( $job_id );
		$admin_email = sanitize_email( get_option( 'admin_email' ) );
		$headers     = [ 'Content-Type: text/html; charset=UTF-8' ];

		// Candidate email.
		$candidate_subject = sprintf( __( 'Your Application for %s', 'job-board-plugin' ), $job_title );
		$candidate_message = '
		<html><body>
			<h2>' . esc_html__( 'Thank you for your application!', 'job-board-plugin' ) . '</h2>
			<p>' . sprintf( esc_html__( 'Hi %s,', 'job-board-plugin' ), esc_html( $full_name ) ) . '</p>
			<p>' . sprintf( esc_html__( 'We have received your application for the position %s.', 'job-board-plugin' ), '<strong>' . esc_html( $job_title ) . '</strong>' ) . '</p>
			<p>' . esc_html__( 'Our team will review your application and get back to you soon.', 'job-board-plugin' ) . '</p>
		</body></html>';
		wp_mail( $email, $candidate_subject, $candidate_message, $headers );

		// Admin email.
		$admin_subject = sprintf( __( 'New Job Application for %s', 'job-board-plugin' ), $job_title );
		$admin_message = '
		<html><body>
			<h2>' . esc_html__( 'New Application Received', 'job-board-plugin' ) . '</h2>
			<p><strong>' . esc_html__( 'Job Title', 'job-board-plugin' ) . ':</strong> ' . esc_html( $job_title ) . '</p>
			<p><strong>' . esc_html__( 'Name', 'job-board-plugin' ) . ':</strong> ' . esc_html( $full_name ) . '</p>
			<p><strong>' . esc_html__( 'Email', 'job-board-plugin' ) . ':</strong> ' . esc_html( $email ) . '</p>
			<p><strong>' . esc_html__( 'Phone', 'job-board-plugin' ) . ':</strong> ' . esc_html( $phone ) . '</p>';

		if ( $resume_url ) {
			$admin_message .= '<p><strong>' . esc_html__( 'Resume', 'job-board-plugin' ) . ':</strong> <a href="' . esc_url( $resume_url ) . '">' . esc_html__( 'Download Resume', 'job-board-plugin' ) . '</a></p>';
		}

		$admin_message .= '</body></html>';
		wp_mail( $admin_email, $admin_subject, $admin_message, $headers );

		wp_send_json_success( __( 'Application submitted successfully.', 'job-board-plugin' ) );
	}

	/**
	 * Add admin menu page for applications.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Job Applications', 'job-board-plugin' ),
			__( 'Job Applications', 'job-board-plugin' ),
			'manage_options',
			'job-applications',
			[ $this, 'render_admin_page' ],
			'dashicons-id'
		);
	}

	/**
	 * Enqueue admin scripts and localize strings.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_job-applications' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'job-apps-admin', JOB_BOARD_PLUGIN_URL . 'assets/js/admin-job-apps.js', [ 'jquery' ], JOB_BOARD_PLUGIN_VERSION, true );
		wp_localize_script( 'job-apps-admin', 'jobAppsData', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'job_app_admin_nonce' ),
			'confirm_delete' => __( 'Are you sure you want to delete this application?', 'job-board-plugin' ),
			'msg_update_success' => __( 'Application updated successfully', 'job-board-plugin' ),
			'msg_delete_success' => __( 'Application deleted successfully', 'job-board-plugin' ),
			'msg_fail' => __( 'An error occurred', 'job-board-plugin' ),
			'edit_title' => __( 'Edit Application', 'job-board-plugin' ),
			'label_name' => __( 'Candidate Name', 'job-board-plugin' ),
			'label_email' => __( 'Email', 'job-board-plugin' ),
			'label_phone' => __( 'Phone', 'job-board-plugin' ),
			'label_resume' => __( 'Resume (PDF)', 'job-board-plugin' ),
			'download_resume' => __( 'Download Resume', 'job-board-plugin' ),
			'btn_save' => __( 'Save', 'job-board-plugin' ),
			'btn_cancel' => __( 'Cancel', 'job-board-plugin' ),
		] );
	}

	/**
	 * Render applications list in admin.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		global $wpdb;

		$table_name   = esc_sql( $wpdb->prefix . 'job_applications' );
	$applications = $wpdb->get_results( "SELECT * FROM `{$table_name}` ORDER BY created_at DESC" );

		echo '<div class="wrap"><h1>' . esc_html__( 'Job Applications', 'job-board-plugin' ) . '</h1>';

		if ( empty( $applications ) ) {
			echo '<p>' . esc_html__( 'No applications yet.', 'job-board-plugin' ) . '</p>';
		} else {
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr>
					<th>' . esc_html__( 'Job Title', 'job-board-plugin' ) . '</th>
					<th>' . esc_html__( 'Candidate Name', 'job-board-plugin' ) . '</th>
					<th>' . esc_html__( 'Email', 'job-board-plugin' ) . '</th>
					<th>' . esc_html__( 'Phone', 'job-board-plugin' ) . '</th>
					<th>' . esc_html__( 'Date', 'job-board-plugin' ) . '</th>
					<th>' . esc_html__( 'Actions', 'job-board-plugin' ) . '</th>
				  </tr></thead><tbody>';

			foreach ( $applications as $app ) {
				$job_title = get_the_title( intval( $app->job_id ) );
				echo '<tr>';
				echo '<td>' . esc_html( $job_title ) . '</td>';
				echo '<td>' . esc_html( $app->full_name ) . '</td>';
				echo '<td>' . esc_html( $app->email ) . '</td>';
				echo '<td>' . esc_html( $app->phone ) . '</td>';
				// Format date nicely; handle zero/empty dates.
				$display_date = '';
				if ( empty( $app->created_at ) || '0000-00-00 00:00:00' === $app->created_at ) {
					$display_date = esc_html__( '-', 'job-board-plugin' );
				} else {
					$ts = strtotime( $app->created_at );
					if ( false === $ts ) {
						$display_date = esc_html( $app->created_at );
					} else {
						$display_date = esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) );
					}
				}
				echo '<td>' . $display_date . '</td>';
				echo '<td>';
				if ( ! empty( $app->resume_url ) ) {
					echo '<a href="' . esc_url( $app->resume_url ) . '" download>' . esc_html__( 'Download Resume', 'job-board-plugin' ) . '</a>';
				}
				echo '</td>';
				echo '<td>';
				// Capability check: only allow Editors and Administrators to edit/delete
				if ( current_user_can( 'edit_others_posts' ) || current_user_can( 'manage_options' ) ) {
					echo '<button class="button job-app-edit" data-id="' . esc_attr( $app->id ) . '">' . esc_html__( 'Edit', 'job-board-plugin' ) . '</button> ';
					echo '<button class="button button-secondary job-app-delete" data-id="' . esc_attr( $app->id ) . '">' . esc_html__( 'Delete', 'job-board-plugin' ) . '</button>';
				} else {
					echo esc_html__( 'No actions', 'job-board-plugin' );
				}
				echo '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		echo '</div>';
	}

	/**
	 * AJAX: return application data for editing.
	 */
	public function ajax_get_application() {
		if ( ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'job-board-plugin' ) );
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'job_app_admin_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'job-board-plugin' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'job-board-plugin' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'job_applications';
		$app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		if ( ! $app ) {
			wp_send_json_error( __( 'Application not found', 'job-board-plugin' ) );
		}

		$data = [
			'id' => intval( $app->id ),
			'full_name' => $app->full_name,
			'email' => $app->email,
			'phone' => $app->phone,
			'resume_url' => $app->resume_url,
		];
		wp_send_json_success( $data );
	}

	/**
	 * AJAX: update application data (including resume file replacement).
	 */
	public function ajax_update_application() {
		if ( ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'job-board-plugin' ) );
		}

		// Check nonce
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'job_app_admin_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'job-board-plugin' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'job-board-plugin' ) );
		}

		$full_name = isset( $_REQUEST['full_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['full_name'] ) ) : '';
		$email = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : '';
		$phone = isset( $_REQUEST['phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) ) : '';

		if ( empty( $full_name ) || empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid data', 'job-board-plugin' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'job_applications';
		$app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		if ( ! $app ) {
			wp_send_json_error( __( 'Application not found', 'job-board-plugin' ) );
		}

		// Handle resume upload if provided
		$resume_url = $app->resume_url;
		if ( isset( $_FILES['resume'] ) && ! empty( $_FILES['resume']['name'] ) ) {
			$file = $_FILES['resume'];
			if ( ! isset( $file['error'] ) || 0 !== $file['error'] ) {
				wp_send_json_error( __( 'Upload error', 'job-board-plugin' ) );
			}

			// Validate PDF
			$is_pdf = false;
			if ( function_exists( 'finfo_open' ) ) {
				$finfo = finfo_open( FILEINFO_MIME_TYPE );
				if ( $finfo ) {
					$mime = finfo_file( $finfo, $file['tmp_name'] );
					finfo_close( $finfo );
					if ( 'application/pdf' === $mime ) {
						$is_pdf = true;
					}
				}
			}
			if ( ! $is_pdf ) {
				$provided_type = isset( $file['type'] ) ? $file['type'] : '';
				$name = isset( $file['name'] ) ? $file['name'] : '';
				if ( 'application/pdf' === $provided_type || 'pdf' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
					$is_pdf = true;
				}
			}
			if ( ! $is_pdf ) {
				wp_send_json_error( __( 'Resume must be a PDF file.', 'job-board-plugin' ) );
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_handle_upload( 'resume', 0 );
			if ( is_wp_error( $attachment_id ) ) {
				wp_send_json_error( __( 'Upload failed', 'job-board-plugin' ) );
			}

			$new_url = wp_get_attachment_url( $attachment_id );
			if ( $new_url ) {
				// Delete old file if present and it's an attachment URL
				if ( ! empty( $app->resume_url ) ) {
					$old_attachment_id = attachment_url_to_postid( $app->resume_url );
					if ( $old_attachment_id ) {
						wp_delete_attachment( $old_attachment_id, true );
					} else {
						// attempt to unlink direct file path
						$old_path = wp_normalize_path( str_replace( site_url( '' ), ABSPATH, $app->resume_url ) );
						if ( file_exists( $old_path ) ) {
							@unlink( $old_path );
						}
					}
				}
				$resume_url = $new_url;
			}
		}

		$updated = $wpdb->update(
			$table,
			[
				'full_name' => $full_name,
				'email' => $email,
				'phone' => $phone,
				'resume_url' => $resume_url,
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s', '%s' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			wp_send_json_error( __( 'Database update failed', 'job-board-plugin' ) );
		}

		wp_send_json_success( __( 'Application updated', 'job-board-plugin' ) );
	}

	/**
	 * AJAX: delete application and associated resume attachment/file.
	 */
	public function ajax_delete_application() {
		if ( ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'job-board-plugin' ) );
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'job_app_admin_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'job-board-plugin' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'job-board-plugin' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'job_applications';
		$app = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		if ( ! $app ) {
			wp_send_json_error( __( 'Application not found', 'job-board-plugin' ) );
		}

		// Delete resume
		if ( ! empty( $app->resume_url ) ) {
			$attach_id = attachment_url_to_postid( $app->resume_url );
			if ( $attach_id ) {
				wp_delete_attachment( $attach_id, true );
			} else {
				$old_path = wp_normalize_path( str_replace( site_url( '' ), ABSPATH, $app->resume_url ) );
				if ( file_exists( $old_path ) ) {
					@unlink( $old_path );
				}
			}
		}

		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		if ( false === $deleted ) {
			wp_send_json_error( __( 'Delete failed', 'job-board-plugin' ) );
		}

		wp_send_json_success( __( 'Application deleted', 'job-board-plugin' ) );
	}
}
