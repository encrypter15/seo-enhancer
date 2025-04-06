document.addEventListener('DOMContentLoaded', function() {
    console.log('SEO Enhancer: JS Loaded - Starting execution');

    // Wait for the editor to be fully ready
    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(function() {
            console.log('SEO Enhancer: Editor DOM ready');

            var loadingElement = document.getElementById('seo-loading');
            if (loadingElement) {
                loadingElement.style.visibility = 'hidden';
                console.log('SEO Enhancer: Loading spinner found and hidden');
            } else {
                console.log('SEO Enhancer: Loading spinner not found');
            }

            // Minimal AJAX call to load the SEO score
            var xhr = new XMLHttpRequest();
            xhr.open('POST', seoEnhancer.ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

            var data = 'action=seo_enhancer_initial_check&nonce=' + seoEnhancer.nonce + '&post_id=' + seoEnhancer.post_id;
            xhr.send(data);

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log('SEO Enhancer: Initial check response:', response);
                        if (response.success) {
                            // Minimal DOM update: just the score
                            var scoreElement = document.getElementById('seo-score-value');
                            if (scoreElement) {
                                scoreElement.textContent = response.data.score;
                                console.log('SEO Enhancer: Score updated to:', response.data.score);
                            } else {
                                console.error('SEO Enhancer: Score element not found');
                            }
                        } else {
                            console.error('SEO Enhancer: Initial check failed:', response.data);
                        }
                    } catch (e) {
                        console.error('SEO Enhancer: Failed to parse response:', e);
                    }
                } else {
                    console.error('SEO Enhancer: Initial check AJAX error:', xhr.status, xhr.statusText);
                }

                if (loadingElement) {
                    loadingElement.style.visibility = 'hidden';
                    loadingElement.classList.remove('is-active');
                    console.log('SEO Enhancer: Loading spinner hidden after AJAX');
                }
            };

            xhr.onerror = function() {
                console.error('SEO Enhancer: Initial check AJAX request failed');
                if (loadingElement) {
                    loadingElement.style.visibility = 'hidden';
                    loadingElement.classList.remove('is-active');
                }
            };
        });
    } else {
        console.error('SEO Enhancer: wp.domReady not available - Script may not run correctly');
    }
});