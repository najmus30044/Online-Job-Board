<?php
defined( 'ABSPATH' ) || exit;

global $wpdb;

// Handle search.
$search_title   = isset( $_GET['search_title'] ) ? sanitize_text_field( wp_unslash( $_GET['search_title'] ) ) : '';
$search_company = isset( $_GET['search_company'] ) ? sanitize_text_field( wp_unslash( $_GET['search_company'] ) ) : '';
$search_address = isset( $_GET['search_address'] ) ? sanitize_text_field( wp_unslash( $_GET['search_address'] ) ) : '';

$paged = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;

$today = current_time( 'Y-m-d' );
$now   = strtotime( $today );

// Include only jobs where current date is between start and deadline (or deadline empty).
$meta_query = [
    'relation' => 'AND',
    [
        'relation' => 'OR',
        [
            'key'     => '_application_start',
            'value'   => $today,
            'compare' => '<=',
            'type'    => 'DATE',
        ],
        [
            'key'     => '_application_start',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key'     => '_application_start',
            'value'   => '',
            'compare' => '=',
        ],
    ],
    [
        'relation' => 'OR',
        [
            'key'     => '_application_deadline',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ],
        [
            'key'     => '_application_deadline',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key'     => '_application_deadline',
            'value'   => '',
            'compare' => '=',
        ],
    ],
];

$args = [
    'post_type'      => 'job_listing',
    'posts_per_page' => 10,
    'paged'          => $paged,
    'meta_query'     => $meta_query,
    's'              => $search_title,
];

// Collect company/address meta queries.
$meta_search = [];

if ( $search_company ) {
    $meta_search[] = [
        'key'     => '_company_name',
        'value'   => $wpdb->esc_like( $search_company ),
        'compare' => 'LIKE',
    ];
}
if ( $search_address ) {
    $meta_search[] = [
        'key'     => '_company_address',
        'value'   => $wpdb->esc_like( $search_address ),
        'compare' => 'LIKE',
    ];
}

if ( ! empty( $meta_search ) ) {
    if ( count( $meta_search ) === 1 ) {
        $args['meta_query'][] = $meta_search[0];
    } else {
        array_unshift( $meta_search, [ 'relation' => 'OR' ] );
        $args['meta_query'][] = $meta_search;
    }
}

$query = new \WP_Query( $args );
?>

<div class="job-board-container">
    <h1 class="job-board-title"><?php esc_html_e( 'Job Portal', 'job-board-plugin' ); ?></h1>

    <div class="job-search-form-wrapper">
        <form method="get" class="job-search-form">
            <div class="search-fields">
                <label for="search_title" class="screen-reader-text"><?php esc_html_e( 'Search by Job Title', 'job-board-plugin' ); ?></label>
                <input type="text" id="search_title" name="search_title" placeholder="<?php esc_attr_e( 'Search by Job Title', 'job-board-plugin' ); ?>" value="<?php echo esc_attr( $search_title ); ?>" class="search-input">

                <label for="search_company" class="screen-reader-text"><?php esc_html_e( 'Search by Company', 'job-board-plugin' ); ?></label>
                <input type="text" id="search_company" name="search_company" placeholder="<?php esc_attr_e( 'Search by Company', 'job-board-plugin' ); ?>" value="<?php echo esc_attr( $search_company ); ?>" class="search-input">

                <label for="search_address" class="screen-reader-text"><?php esc_html_e( 'Search by Address', 'job-board-plugin' ); ?></label>
                <input type="text" id="search_address" name="search_address" placeholder="<?php esc_attr_e( 'Search by Address', 'job-board-plugin' ); ?>" value="<?php echo esc_attr( $search_address ); ?>" class="search-input">
            </div>
            <button type="submit" class="search-button"><?php esc_html_e( 'Search', 'job-board-plugin' ); ?></button>
        </form>
    </div>

    <?php if ( $query->have_posts() ) : ?>
        <ul class="job-listings">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $deadline     = get_post_meta( get_the_ID(), '_application_deadline', true );
                $start        = get_post_meta( get_the_ID(), '_application_start', true );
                $is_closed    = $deadline && strtotime( $deadline ) < $now ? ' <span class="job-closed">(Closed)</span>' : '';
                $company_name = get_post_meta( get_the_ID(), '_company_name', true );

                if ( $start && strtotime( $start ) > $now ) {
                    continue;
                }
                ?>
                <li class="job-listing-item">
                    <a href="<?php the_permalink(); ?>" class="job-title"><?php the_title(); ?></a>
                    <div class="job-details">
                        <span class="job-company"><?php echo esc_html( $company_name ); ?></span>
                        <?php echo wp_kses_post( $is_closed ); ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>

        <?php
        echo paginate_links( [
            'total'     => $query->max_num_pages,
            'current'   => max( 1, $paged ),
            'format'    => '?paged=%#%',
            'prev_text' => __( 'Previous', 'job-board-plugin' ),
            'next_text' => __( 'Next', 'job-board-plugin' ),
        ] );
        ?>

    <?php else : ?>
        <p class="no-jobs"><?php esc_html_e( 'No jobs found.', 'job-board-plugin' ); ?></p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>
