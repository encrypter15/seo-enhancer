jQuery(document).ready(function($) {
    console.log('SEO Enhancer: JS Loaded');
    var $loading = $('#seo-loading');
    $loading.css('visibility', 'hidden');

    $('head').append('<style>' +
        '#seo-enhancer-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 13px; line-height: 1.4em; background: #f5f5f5; padding: 10px; border-radius: 5px; } ' +
        '.seo-score-wrap { margin-bottom: 15px; } ' +
        '.seo-score-wrap p { margin: 0 0 10px; font-weight: 600; color: #333 !important; } ' +
        '.seo-score-value { font-weight: bold; padding: 2px 6px; border-radius: 3px; } ' +
        '.seo-score-low { background: #ffcccc !important; color: #cc0000 !important; } ' +
        '.seo-score-medium { background: #ffe6cc !important; color: #ff8000 !important; } ' +
        '.seo-score-high { background: #ccffcc !important; color: #008000 !important; } ' +
        '.seo-check-group { margin-bottom: 10px; border: 1px solid #e5e5e5; border-radius: 3px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,0.04); } ' +
        '.seo-group-toggle { margin: 0; padding: 10px; cursor: pointer; font-size: 14px; font-weight: 600; background: #f6f7f7; border-bottom: 1px solid #e5e5e5; display: flex; align-items: center; color: #333 !important; } ' +
        '.seo-error-count { background: #cc0000 !important; color: #fff !important; border-radius: 10px; padding: 2px 6px; font-size: 12px; margin-left: 5px; } ' +
        '.seo-toggle-icon { margin-left: auto; font-size: 16px; color: #666 !important; } ' +
        '.seo-group-content { padding: 10px; list-style: none; margin: 0; display: none; } ' +
        '.seo-check-item { margin: 5px 0; display: flex; align-items: center; font-size: 13px; line-height: 1.5; } ' +
        '.seo-passed { color: #008000 !important; } ' +
        '.seo-failed { color: #cc0000 !important; } ' +
        '.seo-check-status { margin-right: 5px; font-size: 16px; line-height: 1; } ' +
        '.seo-check-label { flex: 1; } ' +
        '.seo-tooltip-icon { margin-left: 5px; color: #666 !important; cursor: help; font-size: 14px; line-height: 1; } ' +
        '.seo-tooltip-icon:hover:after { content: attr(data-tooltip); position: absolute; background: #333; color: #fff !important; padding: 5px 10px; border-radius: 3px; top: -30px; left: 0; white-space: normal; width: 200px; z-index: 9999; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); } ' +
        '.seo-keywords-display-wrap { margin: 10px 0; } ' +
        '.seo-keyword-bubble { display: inline-block; background: #ccffcc !important; color: #008000 !important; padding: 2px 8px; margin: 2px; border-radius: 12px; font-size: 12px; } ' +
        '.seo-inputs-wrap { margin-top: 15px; } ' +
        '.seo-inputs-wrap label { display: block; margin: 5px 0; font-weight: 600; color: #333 !important; } ' +
        '.seo-input { width: 100%; padding: 6px 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px; box-sizing: border-box; background: #fff !important; color: #333 !important; } ' +
        '.seo-meta-desc-scrollable { height: 100px; resize: vertical; overflow-y: auto; } ' +
        '.seo-actions-wrap { margin-top: 15px; } ' +
        '.seo-premium-note { margin: 5px 0 0; color: #666 !important; font-size: 12px; font-style: italic; } ' +
        '.seo-results-wrap { margin-top: 10px; font-size: 12px; } ' +
        '</style>');

    function updateScoreDisplay(score, checkGroups, keywords, title, description) {
        console.log('SEO Enhancer: Updating score display with score:', score, 'checkGroups:', checkGroups, 'keywords:', keywords, 'title:', title, 'description:', description);
        $('#seo-score-value').text(score).removeClass('seo-score-low seo-score-medium seo-score-high')
            .addClass(score < 40 ? 'seo-score-low' : (score < 80 ? 'seo-score-medium' : 'seo-score-high'))
            .css({
                'background': score < 40 ? '#ffcccc' : (score < 80 ? '#ffe6cc' : '#ccffcc'),
                'color': score < 40 ? '#cc0000' : (score < 80 ? '#ff8000' : '#008000')
            });
        var html = '';
        Object.keys(checkGroups).forEach(function(group) {
            var checks = checkGroups[group];
            var errorCount = Object.values(checks).filter(check => !check.passed).length;
            html += '<div class="seo-check-group">' +
                    '<h4 class="seo-group-toggle">' +
                    group +
                    (errorCount > 0 ? ' <span class="seo-error-count">' + errorCount + ' Errors</span>' : '') +
                    '<span class="seo-toggle-icon dashicons dashicons-arrow-down-alt2"></span></h4>' +
                    '<ul class="seo-group-content">';
            Object.keys(checks).forEach(function(key) {
                html += '<li class="seo-check-item ' + (checks[key].passed ? 'seo-passed' : 'seo-failed') + '" style="' + (checks[key].passed ? 'color: #008000 !important;' : 'color: #cc0000 !important;') + '">' +
                        '<span class="seo-check-status dashicons ' + (checks[key].passed ? 'dashicons-yes' : 'dashicons-no') + '" style="' + (checks[key].passed ? 'color: #008000 !important;' : 'color: #cc0000 !important;') + '"></span>' +
                        '<span class="seo-check-label">' + checks[key].label + '</span>' +
                        '<span class="seo-tooltip-icon dashicons dashicons-info" data-tooltip="' + (checks[key].description || 'No description available') + '"></span>' +
                        '</li>';
            });
            html += '</ul></div>';
        });
        $('#seo-checks').html(html);

        // Update keywords display
        var keywordsHtml = '';
        if (keywords && keywords.length > 0) {
            keywords.forEach(function(keyword) {
                keywordsHtml += '<span class="seo-keyword-bubble">' + keyword + '</span>';
            });
        } else {
            keywordsHtml = '<p style="color: #666 !important; font-style: italic;">No focus keywords set.</p>';
        }
        $('#seo-keywords-display').html(keywordsHtml);

        // Update input fields
        $('#seo-focus-keywords').val(keywords.join(','));
        $('#seo-title').val(title);
        $('#seo-meta-desc').val(description);
    }

    $(document).on('click', '.seo-group-toggle', function() {
        console.log('SEO Enhancer: Group toggle clicked:', $(this).text());
        var $toggle = $(this).find('.seo-toggle-icon');
        var $content = $(this).next('.seo-group-content');
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $toggle.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        } else {
            $content.slideDown(200);
            $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    });

    $('#seo-focus-keywords').on('input', function() {
        var keywords = $(this).val().split(',').map(function(kw) { return kw.trim(); }).filter(Boolean);
        if (keywords.length > 5) {
            keywords = keywords.slice(0, 5);
            $(this).val(keywords.join(','));
            $('#seo-results').html('<p style="color: #cc0000 !important;">Maximum 5 keywords allowed.</p>');
        }
    });

    $('#seo-save-btn').on('click', function(e) {
        e.preventDefault();
        console.log('SEO Enhancer: Save Changes clicked');
        var keywords = $('#seo-focus-keywords').val().split(',').map(function(kw) { return kw.trim(); }).filter(Boolean);
        if (keywords.length > 5) {
            keywords = keywords.slice(0, 5);
            $('#seo-focus-keywords').val(keywords.join(','));
        }
        var changes = [
            { type: 'focus_keywords', value: keywords },
            { type: 'seo_title', value: $('#seo-title').val() },
            { type: 'meta_description', value: $('#seo-meta-desc').val() },
            { type: 'internal_links', value: $('#seo-internal-links').val() },
            { type: 'external_links', value: $('#seo-external-links').val() }
        ];
        console.log('SEO Enhancer: Committing changes:', changes);
        
        $loading.css('visibility', 'visible').addClass('is-active');
        $.ajax({
            url: seoEnhancer.ajax_url,
            type: 'POST',
            data: {
                action: 'seo_enhancer_commit_changes',
                nonce: seoEnhancer.nonce,
                post_id: seoEnhancer.post_id,
                changes: JSON.stringify(changes)
            },
            success: function(resp) {
                console.log('SEO Enhancer: Commit response:', resp);
                if (resp.success) {
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                        wp.data.dispatch('core/editor').editPost({ title: resp.data.updated_title });
                        console.log('SEO Enhancer: Updated Gutenberg title:', resp.data.updated_title);
                        if (seoEnhancer.seo_plugin === 'rankmath') {
                            setTimeout(function() {
                                wp.data.dispatch('rank-math').updateMeta({
                                    focus_keyword: resp.data.updated_keywords.join(','),
                                    title: resp.data.updated_title,
                                    description: resp.data.updated_description
                                });
                                wp.data.dispatch('rank-math').refreshResults();
                                console.log('SEO Enhancer: Updated Rank Math meta - Keywords:', resp.data.updated_keywords, 'Title:', resp.data.updated_title, 'Description:', resp.data.updated_description);
                            }, 500);
                        }
                    }
                    updateScoreDisplay(resp.data.new_score, resp.data.checks, resp.data.updated_keywords, resp.data.updated_title, resp.data.updated_description);
                    $('#seo-results').html('<p style="color: #008000 !important;">Changes saved successfully!</p>');
                } else {
                    console.error('SEO Enhancer: Commit failed:', resp.data);
                    $('#seo-results').html('<p style="color: #cc0000 !important;">Error: ' + resp.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('SEO Enhancer: Commit AJAX error:', xhr.responseText);
                $('#seo-results').html('<p style="color: #cc0000 !important;">AJAX Error: ' + error + ' - ' + xhr.responseText + '</p>');
            },
            complete: function() {
                $loading.css('visibility', 'hidden').removeClass('is-active');
            }
        });
    });

    // Run initial check on page load
    $loading.css('visibility', 'visible').addClass('is-active');
    $.ajax({
        url: seoEnhancer.ajax_url,
        type: 'POST',
        data: {
            action: 'seo_enhancer_initial_check',
            nonce: seoEnhancer.nonce,
            post_id: seoEnhancer.post_id
        },
        success: function(resp) {
            console.log('SEO Enhancer: Initial check response:', resp);
            if (resp.success) {
                updateScoreDisplay(resp.data.score, resp.data.checks, resp.data.keywords, resp.data.title, resp.data.description);
            } else {
                console.error('SEO Enhancer: Initial check failed:', resp.data);
                $('#seo-results').html('<p style="color: #cc0000 !important;">Error: ' + resp.data + '</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('SEO Enhancer: Initial check AJAX error:', xhr.responseText);
            $('#seo-results').html('<p style="color: #cc0000 !important;">AJAX Error: ' + error + ' - ' + xhr.responseText + '</p>');
        },
        complete: function() {
            $loading.css('visibility', 'hidden').removeClass('is-active');
        }
    });
});