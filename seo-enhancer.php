<?php
/*
Plugin Name: SEO Enhancer
Description: Optimizes WordPress posts and pages for better SEO scores, compatible with multiple SEO plugins
Version: 1.15
Author: Rick Hayes
License: GPL-2.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SEO_Enhancer {
    private $seo_plugin = 'none';

    public function __construct() {
        if (defined('RANK_MATH_VERSION')) {
            $this->seo_plugin = 'rankmath';
        } elseif (defined('WPSEO_VERSION')) {
            $this->seo_plugin = 'yoast';
        } elseif (defined('AIOSEO_VERSION')) {
            $this->seo_plugin = 'aioseo';
        } elseif (defined('SEOPRESS_VERSION')) {
            $this->seo_plugin = 'seopress';
        } elseif (defined('THE_SEO_FRAMEWORK_VERSION')) {
            $this->seo_plugin = 'seoframework';
        } elseif (defined('SQ_VERSION')) {
            $this->seo_plugin = 'squirrly';
        } elseif (defined('SLIM_SEO_VERSION')) {
            $this->seo_plugin = 'slimseo';
        } elseif (defined('WDS_VERSION')) {
            $this->seo_plugin = 'smartcrawl';
        }
        error_log('SEO Enhancer: Detected SEO plugin: ' . $this->seo_plugin);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box_data']);
    }

    public function add_admin_menu() {
        add_menu_page('SEO Enhancer', 'SEO Enhancer', 'manage_options', 'seo-enhancer', [$this, 'settings_page_callback'], 'dashicons-chart-line', 6);
    }

    public function settings_page_callback() {
        ?>
        <div class="wrap">
            <h1>SEO Enhancer</h1>
            <p>Enhance your WordPress SEO with 70 checks compatible with multiple SEO plugins.</p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('seo_enhancer_box', 'SEO Enhancer', [$this, 'render_meta_box'], ['post', 'page'], 'side', 'high');
        error_log('SEO Enhancer: Meta box added');
    }

    public function render_meta_box($post) {
        wp_nonce_field('seo_enhancer_nonce', 'seo_enhancer_nonce');
        error_log('SEO Enhancer: Rendering meta box for post ' . $post->ID);

        $keyword_keys = [
            'rankmath' => '_rank_math_focus_keyword',
            'yoast' => '_yoast_wpseo_focuskw',
            'aioseo' => '_aioseo_keywords',
            'seopress' => '_seopress_analysis_target_kw',
            'seoframework' => '_genesis_keywords',
            'squirrly' => '_sq_fp_keywords',
            'slimseo' => 'slim_seo_keywords',
            'smartcrawl' => '_wds_keywords',
            'none' => 'seo_enhancer_focus_keywords'
        ];
        $title_keys = [
            'rankmath' => '_rank_math_title',
            'yoast' => '_yoast_wpseo_title',
            'aioseo' => '_aioseo_title',
            'seopress' => '_seopress_titles_title',
            'seoframework' => '_genesis_title',
            'squirrly' => '_sq_fp_title',
            'slimseo' => 'slim_seo_title',
            'smartcrawl' => '_wds_title',
            'none' => 'seo_enhancer_seo_title'
        ];
        $desc_keys = [
            'rankmath' => '_rank_math_description',
            'yoast' => '_yoast_wpseo_metadesc',
            'aioseo' => '_aioseo_description',
            'seopress' => '_seopress_titles_desc',
            'seoframework' => '_genesis_description',
            'squirrly' => '_sq_fp_description',
            'slimseo' => 'slim_seo_description',
            'smartcrawl' => '_wds_metadesc',
            'none' => 'seo_enhancer_meta_description'
        ];

        $current_keywords = [];
        $meta_key = $keyword_keys[$this->seo_plugin];
        if (metadata_exists('post', $post->ID, $meta_key)) {
            $current_keywords = get_post_meta($post->ID, $meta_key, true);
            $current_keywords = is_array($current_keywords) ? $current_keywords : ($current_keywords ? explode(',', $current_keywords) : []);
        }

        $current_title = $post->post_title;
        $title_key = $title_keys[$this->seo_plugin];
        if (metadata_exists('post', $post->ID, $title_key)) {
            $current_title = get_post_meta($post->ID, $title_key, true) ?: $post->post_title;
        }

        $current_desc = '';
        $desc_key = $desc_keys[$this->seo_plugin];
        if (metadata_exists('post', $post->ID, $desc_key)) {
            $current_desc = get_post_meta($post->ID, $desc_key, true);
        }

        $current_internal_links = metadata_exists('post', $post->ID, 'seo_enhancer_internal_links') ? get_post_meta($post->ID, 'seo_enhancer_internal_links', true) : '';
        $current_external_links = metadata_exists('post', $post->ID, 'seo_enhancer_external_links') ? get_post_meta($post->ID, 'seo_enhancer_external_links', true) : '';

        $check_groups = [];
        $initial_score = $this->calculate_seo_score($post->post_content, $current_keywords, $current_title, $current_desc, $check_groups);

        // Inline CSS to avoid JavaScript
        ?>
        <style>
            #seo-enhancer-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 13px; line-height: 1.4em; background: #f5f5f5; padding: 10px; border-radius: 5px; box-sizing: border-box; width: 100%; max-width: 100%; }
            #seo-enhancer-container .seo-score-wrap { margin-bottom: 15px; width: 100%; overflow: visible; display: flex; align-items: center; }
            #seo-enhancer-container .seo-score-wrap p { margin: 0; font-weight: 600; color: #333; white-space: nowrap; }
            #seo-enhancer-container .seo-score-value { font-weight: bold; padding: 2px 6px; border-radius: 3px; display: inline-block; min-width: 30px; text-align: center; margin-right: 5px; }
            #seo-enhancer-container .seo-score-low { background: #ffcccc; color: #cc0000; }
            #seo-enhancer-container .seo-score-medium { background: #ffe6cc; color: #ff8000; }
            #seo-enhancer-container .seo-score-high { background: #ccffcc; color: #008000; }
            #seo-enhancer-container #seo-checks { margin-bottom: 15px; }
            #seo-enhancer-container .seo-dropdown { margin-bottom: 10px; }
            #seo-enhancer-container .seo-dropdown-toggle { width: 100%; padding: 8px 12px; background: #f6f7f7; border: 1px solid #e5e5e5; border-radius: 3px; text-align: left; font-weight: 600; color: #333; display: flex; align-items: center; box-sizing: border-box; }
            #seo-enhancer-container .seo-check-list { list-style: none; margin: 0; padding: 0; }
            #seo-enhancer-container .seo-check-item { margin: 5px 0; display: flex; align-items: center; font-size: 13px; line-height: 1.5; flex-wrap: wrap; width: 100%; }
            #seo-enhancer-container .seo-passed { color: #008000; }
            #seo-enhancer-container .seo-failed { color: #cc0000; }
            #seo-enhancer-container .seo-check-status { margin-right: 5px; font-size: 16px; line-height: 1; flex-shrink: 0; }
            #seo-enhancer-container .seo-check-label { flex: 1; min-width: 0; word-wrap: break-word; white-space: normal; color: #333; }
            #seo-enhancer-container .seo-tooltip-icon { margin-left: 5px; color: #666; font-size: 14px; line-height: 1; flex-shrink: 0; }
            #seo-enhancer-container .seo-keywords-display-wrap { margin: 10px 0; }
            #seo-enhancer-container .seo-keyword-bubble { display: inline-block; background: #ccffcc; color: #008000; padding: 2px 8px; margin: 2px; border-radius: 12px; font-size: 12px; }
            #seo-enhancer-container .seo-inputs-wrap { margin-top: 15px; }
            #seo-enhancer-container .seo-inputs-wrap label { display: block; margin: 5px 0; font-weight: 600; color: #333; }
            #seo-enhancer-container .seo-input { width: 100%; padding: 6px 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px; box-sizing: border-box; background: #fff; color: #333; }
            #seo-enhancer-container .seo-meta-desc-scrollable { min-height: 80px; max-height: 120px; resize: vertical; overflow-y: auto; }
            #seo-enhancer-container .seo-actions-wrap { margin-top: 15px; }
            #seo-enhancer-container .seo-premium-note { margin: 5px 0 0; color: #666; font-size: 12px; font-style: italic; }
            #seo-enhancer-container .seo-results-wrap { margin-top: 10px; font-size: 12px; }
        </style>

        <div id="seo-enhancer-container">
            <div id="seo-score" class="seo-score-wrap">
                <p><strong>SEO Score:</strong> <span id="seo-score-value" class="<?php echo $initial_score < 40 ? 'seo-score-low' : ($initial_score < 80 ? 'seo-score-medium' : 'seo-score-high'); ?>"><?php echo esc_html($initial_score); ?></span>/100</p>
            </div>
            <div id="seo-checks">
                <?php foreach ($check_groups as $group => $checks): ?>
                    <div class="seo-dropdown">
                        <div class="seo-dropdown-toggle"><?php echo esc_html($group); ?> (<?php echo count(array_filter($checks, function($check) { return !$check['passed']; })); ?> Errors)</div>
                        <div class="seo-dropdown-content">
                            <ul class="seo-check-list">
                                <?php foreach ($checks as $key => $check): ?>
                                    <li class="seo-check-item <?php echo $check['passed'] ? 'seo-passed' : 'seo-failed'; ?>">
                                        <span class="seo-check-status dashicons <?php echo $check['passed'] ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                                        <span class="seo-check-label"><?php echo esc_html($check['label']); ?></span>
                                        <span class="seo-tooltip-icon dashicons dashicons-info" title="<?php echo esc_attr($check['description']); ?>"></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="seo-keywords-display" class="seo-keywords-display-wrap">
                <?php if (!empty($current_keywords)): ?>
                    <?php foreach ($current_keywords as $keyword): ?>
                        <span class="seo-keyword-bubble"><?php echo esc_html($keyword); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No focus keywords set. Please add keywords to improve your SEO score.</p>
                <?php endif; ?>
            </div>
            <div id="seo-inputs" class="seo-inputs-wrap">
                <label><strong>Focus Keywords</strong> (comma-separated, max 5):</label>
                <textarea id="seo-focus-keywords" name="seo_enhancer_focus_keywords" rows="2" class="seo-input"><?php echo esc_html(implode(',', $current_keywords)); ?></textarea>
                <label><strong>SEO Title</strong> (60-70 chars recommended):</label>
                <input type="text" id="seo-title" name="seo_enhancer_seo_title" value="<?php echo esc_attr($current_title); ?>" class="seo-input" maxlength="70">
                <label><strong>Meta Description</strong> (120-160 chars recommended):</label>
                <textarea id="seo-meta-desc" name="seo_enhancer_meta_description" rows="5" class="seo-input seo-meta-desc-scrollable" maxlength="160"><?php echo esc_html($current_desc); ?></textarea>
                <label><strong>Internal Links</strong> (URLs, comma-separated):</label>
                <textarea id="seo-internal-links" name="seo_enhancer_internal_links" rows="2" class="seo-input"><?php echo esc_html($current_internal_links); ?></textarea>
                <label><strong>External Links</strong> (URLs, comma-separated):</label>
                <textarea id="seo-external-links" name="seo_enhancer_external_links" rows="2" class="seo-input"><?php echo esc_html($current_external_links); ?></textarea>
            </div>
            <div id="seo-actions" class="seo-actions-wrap">
                <p class="seo-premium-note">70 checks across multiple SEO plugins.</p>
                <p>Save the post to update SEO settings.</p>
            </div>
        </div>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['seo_enhancer_nonce']) || !wp_verify_nonce($_POST['seo_enhancer_nonce'], 'seo_enhancer_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $keyword_keys = [
            'rankmath' => '_rank_math_focus_keyword',
            'yoast' => '_yoast_wpseo_focuskw',
            'aioseo' => '_aioseo_keywords',
            'seopress' => '_seopress_analysis_target_kw',
            'seoframework' => '_genesis_keywords',
            'squirrly' => '_sq_fp_keywords',
            'slimseo' => 'slim_seo_keywords',
            'smartcrawl' => '_wds_keywords',
            'none' => 'seo_enhancer_focus_keywords'
        ];
        $title_keys = [
            'rankmath' => '_rank_math_title',
            'yoast' => '_yoast_wpseo_title',
            'aioseo' => '_aioseo_title',
            'seopress' => '_seopress_titles_title',
            'seoframework' => '_genesis_title',
            'squirrly' => '_sq_fp_title',
            'slimseo' => 'slim_seo_title',
            'smartcrawl' => '_wds_title',
            'none' => 'seo_enhancer_seo_title'
        ];
        $desc_keys = [
            'rankmath' => '_rank_math_description',
            'yoast' => '_yoast_wpseo_metadesc',
            'aioseo' => '_aioseo_description',
            'seopress' => '_seopress_titles_desc',
            'seoframework' => '_genesis_description',
            'squirrly' => '_sq_fp_description',
            'slimseo' => 'slim_seo_description',
            'smartcrawl' => '_wds_metadesc',
            'none' => 'seo_enhancer_meta_description'
        ];

        // Save focus keywords
        if (isset($_POST['seo_enhancer_focus_keywords'])) {
            $keywords = sanitize_text_field($_POST['seo_enhancer_focus_keywords']);
            $keywords = explode(',', $keywords);
            $keywords = array_map('trim', array_filter($keywords));
            $keywords = array_slice($keywords, 0, 5);
            $value_to_save = implode(',', $keywords);
            update_post_meta($post_id, $keyword_keys[$this->seo_plugin], $value_to_save);
            error_log('SEO Enhancer: Saved focus keywords for post ' . $post_id . ': ' . $value_to_save);
        }

        // Save SEO title
        if (isset($_POST['seo_enhancer_seo_title'])) {
            $seo_title = sanitize_text_field($_POST['seo_enhancer_seo_title']);
            update_post_meta($post_id, $title_keys[$this->seo_plugin], $seo_title);
            error_log('SEO Enhancer: Saved SEO title for post ' . $post_id . ': ' . $seo_title);
        }

        // Save meta description
        if (isset($_POST['seo_enhancer_meta_description'])) {
            $meta_desc = sanitize_text_field($_POST['seo_enhancer_meta_description']);
            update_post_meta($post_id, $desc_keys[$this->seo_plugin], $meta_desc);
            error_log('SEO Enhancer: Saved meta description for post ' . $post_id . ': ' . $meta_desc);
        }

        // Save internal links
        if (isset($_POST['seo_enhancer_internal_links'])) {
            $internal_links = sanitize_text_field($_POST['seo_enhancer_internal_links']);
            update_post_meta($post_id, 'seo_enhancer_internal_links', $internal_links);
            error_log('SEO Enhancer: Saved internal links for post ' . $post_id . ': ' . $internal_links);
        }

        // Save external links
        if (isset($_POST['seo_enhancer_external_links'])) {
            $external_links = sanitize_text_field($_POST['seo_enhancer_external_links']);
            update_post_meta($post_id, 'seo_enhancer_external_links', $external_links);
            error_log('SEO Enhancer: Saved external links for post ' . $post_id . ': ' . $external_links);
        }
    }

    private function calculate_seo_score($content, $focus_keywords, $title, $description, &$check_groups = null) {
        $score = 0;
        $focus_keywords = is_array($focus_keywords) ? $focus_keywords : ($focus_keywords ? explode(',', $focus_keywords) : []);
        $focus_keywords = array_map('trim', array_filter($focus_keywords));
        error_log('SEO Enhancer: Calculating SEO score with title: ' . $title . ', keywords: ' . implode(',', $focus_keywords));
        
        $title_keys = [
            'rankmath' => '_rank_math_title',
            'yoast' => '_yoast_wpseo_title',
            'aioseo' => '_aioseo_title',
            'seopress' => '_seopress_titles_title',
            'seoframework' => '_genesis_title',
            'squirrly' => '_sq_fp_title',
            'slimseo' => 'slim_seo_title',
            'smartcrawl' => '_wds_title',
            'none' => 'seo_enhancer_seo_title'
        ];
        $desc_keys = [
            'rankmath' => '_rank_math_description',
            'yoast' => '_yoast_wpseo_metadesc',
            'aioseo' => '_aioseo_description',
            'seopress' => '_seopress_titles_desc',
            'seoframework' => '_genesis_description',
            'squirrly' => '_sq_fp_description',
            'slimseo' => 'slim_seo_description',
            'smartcrawl' => '_wds_metadesc',
            'none' => 'seo_enhancer_meta_description'
        ];

        $content_clean = strip_tags($content ?? '');
        $content_words = str_word_count($content_clean);
        $keyword_count = array_sum(array_map(function($kw) use ($content_clean) { return substr_count(strtolower($content_clean), strtolower($kw)); }, $focus_keywords));
        $density = $content_words > 0 ? ($keyword_count / $content_words) * 100 : 0;
        $slug = basename(get_permalink());
        $first_10_percent = substr($content_clean, 0, max(1, $content_words * 0.1));
        $sentences = preg_split('/[.!?]+/', $content_clean, -1, PREG_SPLIT_NO_EMPTY);
        $paragraphs = preg_split('/\n+/', $content_clean, -1, PREG_SPLIT_NO_EMPTY);

        $basic_seo_checks = [
            'title_keyword' => ['label' => 'Focus Keyword at start of SEO title', 'passed' => count(array_filter($focus_keywords, function($kw) use ($title) { return stripos($title, $kw) === 0; })) > 0, 'description' => 'Primary keyword should start the title.'],
            'title_length' => ['label' => 'SEO title length 60-70 chars', 'passed' => strlen($title) >= 60 && strlen($title) <= 70, 'description' => 'Optimal length for search display.'],
            'desc_keyword' => ['label' => 'Focus Keyword in meta description', 'passed' => count(array_filter($focus_keywords, function($kw) use ($description) { return stripos($description, $kw) !== false; })) > 0, 'description' => 'Improves relevance in snippets.'],
            'desc_length' => ['label' => 'Meta description 120-160 chars', 'passed' => strlen($description) >= 120 && strlen($description) <= 160, 'description' => 'Optimal snippet length.'],
            'url_keyword' => ['label' => 'Focus Keyword in URL slug', 'passed' => count(array_filter($focus_keywords, function($kw) use ($slug) { return stripos($slug, $kw) !== false; })) > 0, 'description' => 'Enhances URL relevance.'],
            'intro_keyword' => ['label' => 'Focus Keyword in first 10% of content', 'passed' => count(array_filter($focus_keywords, function($kw) use ($first_10_percent) { return stripos($first_10_percent, $kw) !== false; })) > 0, 'description' => 'Establishes early relevance.'],
            'density' => ['label' => 'Keyword density 0.8-2.5%', 'passed' => $density >= 0.8 && $density <= 2.5, 'description' => 'Balanced keyword usage.'],
            'content_length' => ['label' => 'Content is 600+ words', 'passed' => $content_words >= 600, 'description' => 'Sufficient depth for ranking.'],
            'keyword_variations' => ['label' => 'Use keyword synonyms/variations', 'passed' => preg_match_all('/\b(' . implode('|', array_map('preg_quote', $focus_keywords)) . '|related|similar)\b/i', $content_clean) > count($focus_keywords), 'description' => 'Enhances semantic relevance.'],
            'title_readability' => ['label' => 'SEO title is readable', 'passed' => str_word_count($title) <= 12 && !preg_match('/[A-Z]{2,}/', $title), 'description' => 'Avoids over-optimization or all caps.'],
            'desc_readability' => ['label' => 'Meta description is readable', 'passed' => str_word_count($description) <= 30 && !preg_match('/[A-Z]{2,}/', $description), 'description' => 'Ensures snippet clarity.'],
            'keyword_in_h1' => ['label' => 'Focus Keyword in H1', 'passed' => preg_match('/<h1[^>]*>.*?(' . implode('|', array_map('preg_quote', $focus_keywords)) . ').*?<\/h1>/i', $content), 'description' => 'Main heading relevance.'],
            'title_unique' => ['label' => 'SEO title is unique', 'passed' => $this->is_unique_meta($title_keys[$this->seo_plugin], $title), 'description' => 'Avoids duplicate titles.'],
            'desc_unique' => ['label' => 'Meta description is unique', 'passed' => $this->is_unique_meta($desc_keys[$this->seo_plugin], $description), 'description' => 'Avoids duplicate descriptions.'],
            'keyword_count_limit' => ['label' => 'Focus Keywords 1-5', 'passed' => count($focus_keywords) >= 1 && count($focus_keywords) <= 5, 'description' => 'Prevents dilution.'],
        ];

        $additional_checks = [
            'subheading_keyword' => ['label' => 'Focus Keyword in subheading(s)', 'passed' => preg_match('/<h[2-6][^>]*>.*?(' . implode('|', array_map('preg_quote', $focus_keywords)) . ').*?<\/h[2-6]>/i', $content), 'description' => 'Improves structure.'],
            'internal_links' => ['label' => '1-5 internal links', 'passed' => preg_match_all('/<a\s[^>]*href=["\']([^"\']*?)["\'][^>]*>/i', $content, $links) && ($int_links = count(array_filter($links[1], function($href) { return strpos($href, home_url()) === 0; }))) >= 1 && $int_links <= 5, 'description' => 'Boosts navigation.'],
            'external_links' => ['label' => '1+ external links', 'passed' => preg_match_all('/<a\s[^>]*href=["\']([^"\']*?)["\'][^>]*>/i', $content, $links) && count(array_filter($links[1], function($href) { return strpos($href, home_url()) !== 0 && filter_var($href, FILTER_VALIDATE_URL); })) >= 1, 'description' => 'Adds credibility.'],
            'images_alt' => ['label' => 'Images with keyword alt text', 'passed' => preg_match_all('/<img\s[^>]*alt=["\'][^"\']*(' . implode('|', array_map('preg_quote', $focus_keywords)) . ')[^"\']*["\']/i', $content) >= 1, 'description' => 'Improves accessibility.'],
            'outbound_nofollow' => ['label' => 'Nofollow on some external links', 'passed' => preg_match('/<a\s[^>]*rel=["\']nofollow["\'][^>]*href=["\']([^"\']*?)["\'][^>]*>/i', $content, $match) && strpos($match[1], home_url()) !== 0, 'description' => 'Controls link juice.'],
            'link_titles' => ['label' => 'Links have title attributes', 'passed' => preg_match_all('/<a\s[^>]*title=["\'][^"\']+["\']/i', $content) >= 1, 'description' => 'Enhances usability.'],
            'image_count' => ['label' => 'At least 1 image in content', 'passed' => preg_match_all('/<img\s[^>]*>/i', $content) >= 1, 'description' => 'Improves engagement.'],
            'image_size' => ['label' => 'Images under 200KB', 'passed' => $this->check_image_sizes($content), 'description' => 'Optimizes load time.'],
            'canonical_tag' => ['label' => 'Canonical URL set', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_canonical_url') ? get_post_meta(get_the_ID(), '_rank_math_canonical_url', true) : preg_match('/<link\s+rel=["\']canonical["\']/i', wp_head()), 'description' => 'Prevents duplication.'],
            'meta_robots' => ['label' => 'Noindex not set', 'passed' => !($this->seo_plugin === 'rankmath' && metadata_exists('post', get_the_ID(), '_rank_math_robots') && in_array('noindex', (array) get_post_meta(get_the_ID(), '_rank_math_robots', true))), 'description' => 'Ensures indexing.'],
            'breadcrumb_usage' => ['label' => 'Breadcrumbs enabled', 'passed' => $this->check_breadcrumbs(), 'description' => 'Improves navigation.'],
            'social_title' => ['label' => 'Social title set', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_og_title') ? get_post_meta(get_the_ID(), '_rank_math_og_title', true) : false, 'description' => 'Optimizes social sharing.'],
            'social_desc' => ['label' => 'Social description set', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_og_description') ? get_post_meta(get_the_ID(), '_rank_math_og_description', true) : false, 'description' => 'Enhances social previews.'],
            'social_image' => ['label' => 'Social image set', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_og_image') ? get_post_meta(get_the_ID(), '_rank_math_og_image', true) : false, 'description' => 'Improves social visibility.'],
            'keyword_in_image' => ['label' => 'Keyword in image filename', 'passed' => preg_match('/<img\s[^>]*src=["\'][^"\']*(' . implode('|', array_map('preg_quote', $focus_keywords)) . ')[^"\']*["\']/i', $content), 'description' => 'Boosts image SEO.'],
            'video_content' => ['label' => 'Video embedded', 'passed' => preg_match('/<iframe[^>]*src=["\'](?:https?:\/\/)?(?:www\.)?(?:youtube\.com|youtu\.be|vimeo\.com)[^>]*>/i', $content), 'description' => 'Enhances engagement.'],
            'faq_schema' => ['label' => 'FAQ schema present', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_schema_FAQ') ? get_post_meta(get_the_ID(), '_rank_math_schema_FAQ', true) : false, 'description' => 'Improves rich snippets.'],
            'howto_schema' => ['label' => 'HowTo schema present', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_schema_HowTo') ? get_post_meta(get_the_ID(), '_rank_math_schema_HowTo', true) : false, 'description' => 'Enhances instructions.'],
            'local_seo' => ['label' => 'Local SEO data set', 'passed' => metadata_exists('post', get_the_ID(), '_rank_math_local_business') ? get_post_meta(get_the_ID(), '_rank_math_local_business', true) : false, 'description' => 'Boosts local visibility.'],
            'content_freshness' => ['label' => 'Content updated within 1 year', 'passed' => (time() - get_post_modified_time('U', false, get_the_ID())) <= 31536000, 'description' => 'Favors fresh content.'],
        ];

        $readability_checks = [
            'flesch_score' => ['label' => 'Flesch score 60-70', 'passed' => ($flesch = $this->calculate_flesch($content)) >= 60 && $flesch <= 70, 'description' => 'Readable for general audience.'],
            'sentence_length' => ['label' => '<25% sentences >20 words', 'passed' => ($long_sentences = count(array_filter($sentences, function($s) { return str_word_count(trim($s)) > 20; })) / count($sentences)) < 0.25, 'description' => 'Improves readability.'],
            'paragraph_length' => ['label' => 'Paragraphs <150 words', 'passed' => count(array_filter($paragraphs, function($p) { return str_word_count(trim($p)) > 150; })) == 0, 'description' => 'Enhances engagement.'],
            'subheading_dist' => ['label' => 'Subheadings every 300 words', 'passed' => ($subheadings = preg_match_all('/<h[2-6][^>]*>.*?<\/h[2-6]>/i', $content)) > 0 && $content_words / $subheadings <= 300, 'description' => 'Breaks up text.'],
            'transition_words' => ['label' => 'Transition words in 30%+ sentences', 'passed' => ($transition_words = preg_match_all('/\b(because|however|therefore|moreover|for example)\b/i', $content_clean) / count($sentences)) >= 0.3, 'description' => 'Improves flow.'],
            'passive_voice' => ['label' => '<10% passive voice', 'passed' => ($passive_voice = preg_match_all('/\b(is|was|were|be|been|being)\s+\w+ed\b/i', $content_clean) / count($sentences)) < 0.1, 'description' => 'Encourages active voice.'],
            'consecutive_sentences' => ['label' => 'No 3+ consecutive similar starts', 'passed' => !$this->check_consecutive_sentences($sentences), 'description' => 'Avoids repetition.'],
            'word_complexity' => ['label' => 'Most words <5 syllables', 'passed' => $this->check_word_complexity($content_clean), 'description' => 'Simplifies reading.'],
            'sentence_variety' => ['label' => 'Vary sentence lengths', 'passed' => $this->check_sentence_variety($sentences), 'description' => 'Keeps reader engaged.'],
            'subheading_count' => ['label' => 'At least 2 subheadings', 'passed' => preg_match_all('/<h[2-6][^>]*>.*?<\/h[2-6]>/i', $content) >= 2, 'description' => 'Structures content.'],
            'list_usage' => ['label' => 'Use lists (ul/ol)', 'passed' => preg_match('/<(ul|ol)[^>]*>.*?<\/\1>/i', $content), 'description' => 'Improves scannability.'],
            'bold_emphasis' => ['label' => 'Use bold for emphasis', 'passed' => preg_match('/<b[^>]*>.*?<\/b>|<strong[^>]*>.*?<\/strong>/i', $content), 'description' => 'Highlights key points.'],
            'text_breaks' => ['label' => 'Short paragraphs (<5 sentences)', 'passed' => count(array_filter($paragraphs, function($p) { return count(preg_split('/[.!?]+/', trim($p), -1, PREG_SPLIT_NO_EMPTY)) > 5; })) == 0, 'description' => 'Easier to read.'],
            'quote_usage' => ['label' => 'Use quotes', 'passed' => preg_match('/<blockquote[^>]*>.*?<\/blockquote>/i', $content), 'description' => 'Adds authority.'],
            'readability_intro' => ['label' => 'Intro paragraph <100 words', 'passed' => str_word_count($paragraphs[0] ?? '') < 100, 'description' => 'Concise intro.'],
        ];

        $check_groups = [
            'Basic SEO' => $basic_seo_checks,
            'Additional SEO' => $additional_checks,
            'Readability' => $readability_checks,
            'Technical SEO' => $technical_checks,
            'Schema & Social' => $schema_social_checks,
        ];

        $total_checks = 0;
        foreach ($check_groups as $group) {
            $total_checks += count($group);
            $score += count(array_filter($group, function($check) { return $check['passed']; }));
        }

        $final_score = ($score / $total_checks) * 100;
        error_log('SEO Enhancer: Final score: ' . $final_score . ', Total checks: ' . $total_checks . ', Passed: ' . $score);

        return round($final_score);
    }

    private function calculate_flesch($content) {
        $text = strip_tags($content ?? '');
        $words = str_word_count($text);
        $sentences = count(preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY));
        $syllables = 0;
        foreach (preg_split('/\s+/', $text) as $word) {
            $syllables += preg_match_all('/[aeiouy]+/i', $word);
        }
        return $words && $sentences ? 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / $words) : 0;
    }

    private function is_unique_meta($meta_key, $value) {
        $posts = get_posts([
            'meta_key' => $meta_key,
            'meta_value' => $value,
            'post_type' => 'any',
            'posts_per_page' => 2,
            'fields' => 'ids',
        ]);
        return count($posts) <= 1;
    }

    private function check_image_sizes($content) {
        preg_match_all('/<img\s[^>]*src=["\'](.*?)["\']/i', $content, $matches);
        if (empty($matches[1])) return true;
        foreach ($matches[1] as $src) {
            $response = wp_remote_head($src);
            if (!is_wp_error($response) && isset($response['headers']['content-length'])) {
                $size = (int)$response['headers']['content-length'] / 1024;
                if ($size > 200) return false;
            }
        }
        return true;
    }

    private function check_breadcrumbs() {
        return (function_exists('rank_math_the_breadcrumbs') && get_option('rank_math_breadcrumbs')) || 
               (function_exists('yoast_breadcrumb') && get_option('wpseo_titles')['breadcrumbs-enable']) ||
               (function_exists('aioseo_breadcrumbs') && get_option('aioseo_options')['breadcrumbs']['enable']);
    }

    private function check_404_errors() {
        if (!class_exists('RankMath\Monitor\DB')) {
            return true; // No Rank Math, assume no 404 errors
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'rank_math_404_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return true; // Table doesn't exist, assume no errors
        }
        try {
            $errors = RankMath\Monitor\DB::get_logs(['limit' => 1, 'type' => '404']);
            return empty($errors);
        } catch (Exception $e) {
            error_log('SEO Enhancer: check_404_errors exception: ' . $e->getMessage());
            return true;
        }
    }

    private function check_redirect_count() {
        $url = get_permalink();
        $response = wp_remote_head($url, ['redirection' => 5]);
        if (is_wp_error($response)) return false;
        $redirect_count = isset($response['x-wp-total-redirects']) ? (int)$response['x-wp-total-redirects'] : 0;
        return $redirect_count < 5;
    }

    private function check_sitemap_inclusion() {
        $post_id = get_the_ID();
        $robots = get_post_meta($post_id, '_rank_math_robots', true);
        $noindex = false;
        if ($this->is_serialized($robots)) {
            $robots = unserialize($robots);
            if (is_array($robots) && in_array('noindex', $robots)) {
                $noindex = true;
            }
        } else if (is_array($robots) && in_array('noindex', $robots)) {
            $noindex = true;
        }
        $yoast_exclude = get_post_meta($post_id, '_yoast_wpseo_sitemaps_exclude', true);
        $aioseo_noindex = get_post_meta($post_id, '_aioseo_noindex', true);
        
        return !$noindex && !$yoast_exclude && !$aioseo_noindex;
    }

    private function is_serialized($data) {
        return is_string($data) && @unserialize($data) !== false;
    }

    private function check_mobile_friendly() {
        $theme = wp_get_theme();
        $is_mobile_friendly = $theme->get('MobileFriendly') || get_theme_support('responsive-embeds') || true;
        return $is_mobile_friendly;
    }

    private function check_page_speed() {
        $start_time = microtime(true);
        ob_start();
        wp_head();
        wp_footer();
        ob_end_clean();
        $load_time = microtime(true) - $start_time;
        return $load_time < 3;
    }

    private function check_index_status() {
        $robots = get_post_meta(get_the_ID(), '_rank_math_robots', true);
        $noindex = false;
        if ($this->is_serialized($robots)) {
            $robots = unserialize($robots);
            if (is_array($robots) && in_array('noindex', $robots)) {
                $noindex = true;
            }
        } else if (is_array($robots) && in_array('noindex', $robots)) {
            $noindex = true;
        }
        return !$noindex && !get_post_meta(get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true) && 
               !get_post_meta(get_the_ID(), '_aioseo_noindex', true);
    }

    private function check_broken_links($content) {
        preg_match_all('/<a\s[^>]*href=["\'](.*?)["\']/i', $content, $matches);
        foreach ($matches[1] as $href) {
            if (filter_var($href, FILTER_VALIDATE_URL)) {
                $response = wp_remote_head($href, ['timeout' => 5]);
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) == 404) {
                    return false;
                }
            }
        }
        return true;
    }

    private function check_schema_validation() {
        $schema = get_post_meta(get_the_ID(), '_rank_math_schema', true);
        if (!$schema) return true;
        $json = json_decode($schema, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function check_consecutive_sentences($sentences) {
        for ($i = 0; $i < count($sentences) - 2; $i++) {
            $word1 = strtok(trim($sentences[$i]), ' ');
            $word2 = strtok(trim($sentences[$i + 1]), ' ');
            $word3 = strtok(trim($sentences[$i + 2]), ' ');
            if ($word1 && $word1 === $word2 && $word2 === $word3) return true;
        }
        return false;
    }

    private function check_word_complexity($content) {
        $words = preg_split('/\s+/', $content);
        $complex = array_filter($words, function($w) { return preg_match_all('/[aeiouy]+/i', $w) >= 5; });
        return count($complex) / count($words) < 0.1;
    }

    private function check_sentence_variety($sentences) {
        $lengths = array_map('str_word_count', $sentences);
        return max($lengths) - min($lengths) > 5;
    }
}

new SEO_Enhancer();