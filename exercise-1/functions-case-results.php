<?php
/**
 * Prefix: nxs_ (NuroSparX)
 * Register "Case Results" custom post type.
 */
add_action('init', 'nxs_register_case_results_cpt');
function nxs_register_case_results_cpt() {
    $labels = array(
        'name'               => __('Case Results', 'nxs'),
        'singular_name'      => __('Case Result', 'nxs'),
        'add_new'            => __('Add New Case Result', 'nxs'),
        'add_new_item'       => __('Add New Case Result', 'nxs'),
        'edit_item'          => __('Edit Case Result', 'nxs'),
        'new_item'           => __('New Case Result', 'nxs'),
        'view_item'          => __('View Case Result', 'nxs'),
        'search_items'       => __('Search Case Results', 'nxs'),
        'not_found'          => __('No case results found', 'nxs'),
        'not_found_in_trash' => __('No case results found in trash', 'nxs'),
        'menu_name'          => __('Case Results', 'nxs')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_in_rest'       => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'case-results'),
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-analytics',
    );

    register_post_type('case_result', $args);
}

/**
 * Add metabox for case details.
 */
add_action('add_meta_boxes', 'nxs_add_case_results_metabox');
function nxs_add_case_results_metabox() {
    add_meta_box(
        'nxs_case_details',
        __('Case Details', 'nxs'),
        'nxs_render_case_details_metabox',
        'case_result',
        'normal',
        'high'
    );
}

/**
 * Render metabox fields.
 */
function nxs_render_case_details_metabox($post) {
    wp_nonce_field('nxs_save_case_details', 'nxs_case_details_nonce');

    $case_type          = get_post_meta($post->ID, '_nxs_case_type', true);
    $settlement_amount  = get_post_meta($post->ID, '_nxs_settlement_amount', true);
    $case_duration      = get_post_meta($post->ID, '_nxs_case_duration_months', true);
    $client_location    = get_post_meta($post->ID, '_nxs_client_location', true);
    $case_year          = get_post_meta($post->ID, '_nxs_case_year', true);

    $case_types = array(
        'personal_injury'      => 'Personal Injury',
        'car_accident'         => 'Car Accident',
        'slip_and_fall'        => 'Slip & Fall',
        'medical_malpractice'  => 'Medical Malpractice',
    );
    ?>
    <p>
        <label for="nxs_case_type"><strong><?php _e('Case Type', 'nxs'); ?></strong></label><br>
        <select name="nxs_case_type" id="nxs_case_type">
            <option value=""><?php _e('Select Case Type', 'nxs'); ?></option>
            <?php foreach ($case_types as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($case_type, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label for="nxs_settlement_amount"><strong><?php _e('Settlement Amount (USD)', 'nxs'); ?></strong></label><br>
        <input type="text"
               name="nxs_settlement_amount"
               id="nxs_settlement_amount"
               value="<?php echo esc_attr($settlement_amount); ?>"
               placeholder="$150,000.00"
               style="max-width: 200px;">
        <small><?php _e('Numbers only or formatted currency, will be normalized.', 'nxs'); ?></small>
    </p>

    <p>
        <label for="nxs_case_duration"><strong><?php _e('Case Duration (months)', 'nxs'); ?></strong></label><br>
        <input type="number"
               name="nxs_case_duration"
               id="nxs_case_duration"
               value="<?php echo esc_attr($case_duration); ?>"
               min="0"
               style="max-width: 100px;">
    </p>

    <p>
        <label for="nxs_client_location"><strong><?php _e('Client Location (City, State)', 'nxs'); ?></strong></label><br>
        <input type="text"
               name="nxs_client_location"
               id="nxs_client_location"
               value="<?php echo esc_attr($client_location); ?>"
               style="max-width: 300px;">
    </p>

    <p>
        <label for="nxs_case_year"><strong><?php _e('Case Year', 'nxs'); ?></strong></label><br>
        <input type="number"
               name="nxs_case_year"
               id="nxs_case_year"
               value="<?php echo esc_attr($case_year); ?>"
               min="1980"
               max="<?php echo esc_attr(date('Y')); ?>"
               style="max-width: 120px;">
    </p>
    <?php
}

/**
 * Save metabox data.
 */
add_action('save_post_case_result', 'nxs_save_case_details_metabox');
function nxs_save_case_details_metabox($post_id) {
    if (!isset($_POST['nxs_case_details_nonce']) ||
        !wp_verify_nonce($_POST['nxs_case_details_nonce'], 'nxs_save_case_details')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Case type – whitelist options.
    $allowed_case_types = array(
        'personal_injury',
        'car_accident',
        'slip_and_fall',
        'medical_malpractice',
    );
    $case_type = isset($_POST['nxs_case_type']) ? sanitize_text_field($_POST['nxs_case_type']) : '';
    if ($case_type && !in_array($case_type, $allowed_case_types, true)) {
        $case_type = '';
    }
    update_post_meta($post_id, '_nxs_case_type', $case_type);

    // Settlement amount – normalize to float, store as plain number.
    if (isset($_POST['nxs_settlement_amount'])) {
        $raw = wp_unslash($_POST['nxs_settlement_amount']);
        $normalized = preg_replace('/[^0-9.]/', '', $raw);
        $amount = $normalized !== '' ? (float) $normalized : '';
        update_post_meta($post_id, '_nxs_settlement_amount', $amount);
    }

    // Case duration.
    if (isset($_POST['nxs_case_duration'])) {
        $duration = (int) $_POST['nxs_case_duration'];
        update_post_meta($post_id, '_nxs_case_duration_months', $duration);
    }

    // Client location.
    if (isset($_POST['nxs_client_location'])) {
        update_post_meta($post_id, '_nxs_client_location', sanitize_text_field($_POST['nxs_client_location']));
    }

    // Case year.
    if (isset($_POST['nxs_case_year'])) {
        $year = (int) $_POST['nxs_case_year'];
        if ($year >= 1980 && $year <= (int) date('Y') + 1) {
            update_post_meta($post_id, '_nxs_case_year', $year);
        }
    }
}

/**
 * Helper: map stored case type keys to human labels.
 */
function nxs_get_case_type_label($key) {
    $map = array(
        'personal_injury'      => 'Personal Injury',
        'car_accident'         => 'Car Accident',
        'slip_and_fall'        => 'Slip & Fall',
        'medical_malpractice'  => 'Medical Malpractice',
    );
    return isset($map[$key]) ? $map[$key] : '';
}

/**
 * Display 5 most recent high-value cases (> 100000).
 * Use as template tag or via shortcode [high_value_cases].
 */
function nxs_render_high_value_cases_grid() {
    $q = new WP_Query(array(
        'post_type'      => 'case_result',
        'posts_per_page' => 5,
        'meta_key'       => '_nxs_settlement_amount',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => '_nxs_settlement_amount',
                'value'   => 100000,
                'type'    => 'NUMERIC',
                'compare' => '>=',
            ),
        ),
        'no_found_rows'  => true,
    ));

    if (!$q->have_posts()) {
        echo '<p>' . esc_html__('No high-value cases available yet.', 'nxs') . '</p>';
        return;
    }

    echo '<div class="nxs-case-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;">';

    while ($q->have_posts()) : $q->the_post();
        $id                = get_the_ID();
        $case_type_key     = get_post_meta($id, '_nxs_case_type', true);
        $case_type_label   = nxs_get_case_type_label($case_type_key);
        $settlement_amount = (float) get_post_meta($id, '_nxs_settlement_amount', true);
        $case_duration     = (int) get_post_meta($id, '_nxs_case_duration_months', true);
        $client_location   = get_post_meta($id, '_nxs_client_location', true);
        $case_year         = (int) get_post_meta($id, '_nxs_case_year', true);

        $formatted_amount  = $settlement_amount ? '$' . number_format($settlement_amount, 0) : '';
        ?>
        <article class="nxs-case-card"
                 itemscope
                 itemtype="https://schema.org/LegalCase"
                 data-case-type="<?php echo esc_attr($case_type_label); ?>"
                 data-settlement-amount="<?php echo esc_attr($settlement_amount); ?>"
                 style="border:1px solid #e2e2e2;border-radius:8px;padding:16px;background:#fff;">

            <a href="<?php the_permalink(); ?>" class="nxs-case-link" style="text-decoration:none;color:inherit;">
                <h3 itemprop="name" style="font-size:1.1rem;margin-bottom:8px;"><?php the_title(); ?></h3>

                <meta itemprop="caseStatus" content="Closed">
                <?php if ($case_type_label): ?>
                    <p style="margin:0 0 4px;">
                        <strong>Case Type:</strong>
                        <span itemprop="legalType"><?php echo esc_html($case_type_label); ?></span>
                    </p>
                <?php endif; ?>

                <?php if ($formatted_amount): ?>
                    <p style="margin:0 0 4px;">
                        <strong>Settlement:</strong>
                        <span itemprop="award"><?php echo esc_html($formatted_amount); ?></span>
                    </p>
                <?php endif; ?>

                <?php if ($case_duration): ?>
                    <p style="margin:0 0 4px;">
                        <strong>Duration:</strong>
                        <span><?php echo esc_html($case_duration); ?> months</span>
                    </p>
                <?php endif; ?>

                <?php if ($client_location): ?>
                    <p style="margin:0 0 4px;">
                        <strong>Location:</strong>
                        <span itemprop="location"><?php echo esc_html($client_location); ?></span>
                    </p>
                <?php endif; ?>

                <?php if ($case_year): ?>
                    <meta itemprop="dateResolved" content="<?php echo esc_attr($case_year); ?>-01-01">
                    <p style="margin:0;">
                        <strong>Year:</strong>
                        <?php echo esc_html($case_year); ?>
                    </p>
                <?php endif; ?>

                <div itemprop="description" style="margin-top:8px;font-size:0.9rem;color:#555;">
                    <?php echo wp_kses_post(wp_trim_words(get_the_excerpt() ?: get_the_content(), 20)); ?>
                </div>
            </a>
        </article>
        <?php
    endwhile;
    echo '</div>';
    wp_reset_postdata();
}

add_shortcode('high_value_cases', 'nxs_render_high_value_cases_grid');

/**
 * Enqueue JS for AJAX filter + GTM tracking on archive page.
 */
add_action('wp_enqueue_scripts', 'nxs_enqueue_case_results_scripts');
function nxs_enqueue_case_results_scripts() {
    if (is_post_type_archive('case_result')) {
        wp_enqueue_script(
            'nxs-case-results-js',
            get_stylesheet_directory_uri() . '/exercise-1/js/case-results.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script('nxs-case-results-js', 'nxsCaseResults', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('nxs_filter_cases'),
        ));
    }
}

/**
 * AJAX handler for filtering case results by case type.
 */
add_action('wp_ajax_nxs_filter_case_results', 'nxs_filter_case_results');
add_action('wp_ajax_nopriv_nxs_filter_case_results', 'nxs_filter_case_results');

function nxs_filter_case_results() {
    check_ajax_referer('nxs_filter_cases', 'nonce');

    $case_type = isset($_POST['case_type']) ? sanitize_text_field($_POST['case_type']) : '';

    $meta_query = array();
    if ($case_type) {
        $meta_query[] = array(
            'key'   => '_nxs_case_type',
            'value' => $case_type,
        );
    }

    $args = array(
        'post_type'      => 'case_result',
        'posts_per_page' => 12,
        'meta_query'     => $meta_query,
        'no_found_rows'  => true,
    );

    $q = new WP_Query($args);

    ob_start();

    if ($q->have_posts()) :
        while ($q->have_posts()) : $q->the_post();
            $id                = get_the_ID();
            $case_type_key     = get_post_meta($id, '_nxs_case_type', true);
            $case_type_label   = nxs_get_case_type_label($case_type_key);
            $settlement_amount = (float) get_post_meta($id, '_nxs_settlement_amount', true);
            $formatted_amount  = $settlement_amount ? '$' . number_format($settlement_amount, 0) : '';
            ?>
            <article class="nxs-case-card"
                     data-case-type="<?php echo esc_attr($case_type_label); ?>"
                     data-settlement-amount="<?php echo esc_attr($settlement_amount); ?>">
                <a href="<?php the_permalink(); ?>" class="nxs-case-link">
                    <h3><?php the_title(); ?></h3>
                    <?php if ($case_type_label): ?>
                        <p><strong>Type:</strong> <?php echo esc_html($case_type_label); ?></p>
                    <?php endif; ?>
                    <?php if ($formatted_amount): ?>
                        <p><strong>Settlement:</strong> <?php echo esc_html($formatted_amount); ?></p>
                    <?php endif; ?>
                </a>
            </article>
            <?php
        endwhile;
    else :
        echo '<p>' . esc_html__('No cases found for this filter.', 'nxs') . '</p>';
    endif;

    wp_reset_postdata();

    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
    ));
}
