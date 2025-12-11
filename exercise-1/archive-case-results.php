<?php
/**
 * Archive template for Case Results.
 *
 */
get_header();

// Get all case types for filter dropdown.
$case_types = array(
    ''                    => __('All Case Types', 'nxs'),
    'personal_injury'     => 'Personal Injury',
    'car_accident'        => 'Car Accident',
    'slip_and_fall'       => 'Slip & Fall',
    'medical_malpractice' => 'Medical Malpractice',
);
?>
<main id="primary" class="site-main">
    <header class="page-header">
        <h1><?php post_type_archive_title(); ?></h1>
        <p><?php esc_html_e('Browse our recent case results. Filter by case type to see relevant outcomes.', 'nxs'); ?></p>
    </header>

    <section class="nxs-case-filter" style="margin-bottom: 24px;">
        <label for="nxs_case_filter_select"><strong><?php esc_html_e('Filter by Case Type:', 'nxs'); ?></strong></label>
        <select id="nxs_case_filter_select">
            <?php foreach ($case_types as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </section>

    <section id="nxs_case_results_container" class="nxs-case-archive-grid"
             style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post();
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
                        <h2><?php the_title(); ?></h2>
                        <?php if ($case_type_label): ?>
                            <p><strong><?php esc_html_e('Type:', 'nxs'); ?></strong> <?php echo esc_html($case_type_label); ?></p>
                        <?php endif; ?>
                        <?php if ($formatted_amount): ?>
                            <p><strong><?php esc_html_e('Settlement:', 'nxs'); ?></strong> <?php echo esc_html($formatted_amount); ?></p>
                        <?php endif; ?>
                        <div class="excerpt">
                            <?php echo wp_kses_post(wp_trim_words(get_the_excerpt(), 20)); ?>
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>

            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No case results found.', 'nxs'); ?></p>
        <?php endif; ?>
    </section>
</main>

<?php get_footer(); ?>
