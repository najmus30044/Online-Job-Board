<?php
namespace JobBoardPlugin;

defined( 'ABSPATH' ) || exit;

class Job_CPT {
    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_job_listing', [ $this, 'save_meta' ] );
    }

    public function register_cpt() {
        $labels = [
            'name'          => __( 'Job Listings', 'job-board-plugin' ),
            'singular_name' => __( 'Job Listing', 'job-board-plugin' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'job-listing' ],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => [ 'title', 'editor', 'author', 'thumbnail' ],
        ];

        register_post_type( 'job_listing', $args );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'job_details',
            __( 'Job Details', 'job-board-plugin' ),
            [ $this, 'render_meta_boxes' ],
            'job_listing',
            'normal',
            'high'
        );
    }

    public function render_meta_boxes( $post ) {
        wp_nonce_field( 'job_meta_nonce', 'job_meta_nonce' );
        $company_name    = get_post_meta( $post->ID, '_company_name', true );
        $company_address = get_post_meta( $post->ID, '_company_address', true );
        $deadline        = get_post_meta( $post->ID, '_application_deadline', true );
        $start_date      = get_post_meta( $post->ID, '_application_start', true );
        ?>
        <p>
            <label for="company_name"><?php esc_html_e( 'Company Name', 'job-board-plugin' ); ?></label>
            <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $company_name ); ?>" class="widefat">
        </p>
        <p>
            <label for="company_address"><?php esc_html_e( 'Company Address', 'job-board-plugin' ); ?></label>
            <input type="text" id="company_address" name="company_address" value="<?php echo esc_attr( $company_address ); ?>" class="widefat">
        </p>
        <p>
            <label for="application_start"><?php esc_html_e( 'Application Start Date', 'job-board-plugin' ); ?></label>
            <input type="date" id="application_start" name="application_start" value="<?php echo esc_attr( $start_date ); ?>">
        </p>
        <p>
            <label for="application_deadline"><?php esc_html_e( 'Application Deadline', 'job-board-plugin' ); ?></label>
            <input type="date" id="application_deadline" name="application_deadline" value="<?php echo esc_attr( $deadline ); ?>">
        </p>
        <?php
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['job_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['job_meta_nonce'] ) ), 'job_meta_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [ 'company_name', 'company_address', 'application_start', 'application_deadline' ];
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
}