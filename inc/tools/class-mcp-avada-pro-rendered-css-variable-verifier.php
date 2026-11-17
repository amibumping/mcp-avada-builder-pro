<?php

if (!defined('ABSPATH')) {
    exit;
}

class MCP_Avada_Pro_Rendered_CSS_Variable_Verifier implements MCP_Avada_Pro_Module_Interface
{
    public function register(): void
    {
        add_action('wp_abilities_api_init', array($this, 'register_ability'));
    }

    public function register_ability(): void
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        wp_register_ability(
            'avada-pro/verify-rendered-css-variables',
            array(
                'label' => __('Verify Rendered CSS Variables (Pro)', 'mcp-avada-builder-pro'),
                'description' => __('Fetch rendered frontend HTML and group Avada CSS variable signatures for a target class.', 'mcp-avada-builder-pro'),
                'category' => 'avada-builder-pro',
                'execute_callback' => array($this, 'execute'),
                'permission_callback' => function (): bool {
                    return current_user_can('edit_posts');
                },
                'input_schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'page_url' => array('type' => 'string'),
                        'class_name' => array('type' => 'string'),
                    ),
                    'required' => array('page_url', 'class_name'),
                ),
                'output_schema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'success' => array('type' => 'boolean'),
                        'data' => array('type' => 'object'),
                    ),
                ),
                'meta' => array(
                    'show_in_rest' => true,
                    'mcp' => array('public' => true, 'type' => 'tool'),
                    'annotations' => array(
                        'readonly' => true,
                        'destructive' => false,
                        'idempotent' => true,
                    ),
                ),
            )
        );
    }

    public function execute(array $params): array
    {
        $page_url = isset($params['page_url']) ? esc_url_raw((string) $params['page_url']) : '';
        $class_name = isset($params['class_name']) ? sanitize_text_field((string) $params['class_name']) : '';

        if (empty($page_url) || empty($class_name)) {
            return array(
                'success' => false,
                'data' => array('error' => 'page_url and class_name are required.'),
            );
        }

        $html = self::fetch_site_page($page_url);
        if (is_wp_error($html)) {
            return array(
                'success' => false,
                'data' => array('error' => $html->get_error_message()),
            );
        }

        $report = self::analyze_html_by_class($html, $class_name);
        $report['page_url'] = $page_url;

        return array(
            'success' => true,
            'data' => $report,
        );
    }

    /**
     * Fetch rendered HTML for a URL on this site only.
     *
     * Restricting the host to home_url() prevents the ability from being used
     * as an SSRF proxy (internal services, cloud metadata, port probing).
     *
     * @return string|WP_Error
     */
    public static function fetch_site_page(string $url)
    {
        $site = wp_parse_url(home_url());
        $site_host = isset($site['host']) ? $site['host'] : '';
        $parts = wp_parse_url($url);
        $default_port = function ($p) {
            if (!empty($p['port'])) {
                return (int) $p['port'];
            }
            return (isset($p['scheme']) && 'https' === strtolower($p['scheme'])) ? 443 : 80;
        };

        // Same host AND same port: other ports on this host can be different services.
        if (
            empty($parts['host'])
            || empty($parts['scheme'])
            || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
            || strtolower($parts['host']) !== strtolower((string) $site_host)
            || $default_port($parts) !== $default_port($site)
        ) {
            return new WP_Error('mcp_avada_pro_url_not_allowed', 'page_url must be a URL on this site (' . $site_host . ').');
        }

        $response = wp_safe_remote_get($url, array('timeout' => 20, 'redirection' => 3));
        if (is_wp_error($response)) {
            return new WP_Error('mcp_avada_pro_fetch_failed', 'Could not fetch page_url.');
        }

        return (string) wp_remote_retrieve_body($response);
    }

    public static function analyze_html_by_class(string $html, string $class_name): array
    {
        $pattern = '/class="[^"]*\\b' . preg_quote($class_name, '/') . '\\b[^"]*"\\s+style="([^"]*)"/i';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $rows = array();
        foreach ($matches as $match) {
            $style = $match[1];
            $rows[] = array(
                'width_large' => self::extract_style_value($style, '--awb-width-large'),
                'width_medium' => self::extract_style_value($style, '--awb-width-medium'),
                'width_small' => self::extract_style_value($style, '--awb-width-small'),
                'spacing_left_large' => self::extract_style_value($style, '--awb-spacing-left-large'),
                'spacing_right_large' => self::extract_style_value($style, '--awb-spacing-right-large'),
                'spacing_left_medium' => self::extract_style_value($style, '--awb-spacing-left-medium'),
                'spacing_right_medium' => self::extract_style_value($style, '--awb-spacing-right-medium'),
                'spacing_left_small' => self::extract_style_value($style, '--awb-spacing-left-small'),
                'spacing_right_small' => self::extract_style_value($style, '--awb-spacing-right-small'),
            );
        }

        $groups = array();
        foreach ($rows as $row) {
            $signature = implode('|', $row);
            if (!isset($groups[$signature])) {
                $groups[$signature] = 0;
            }
            $groups[$signature]++;
        }

        arsort($groups);

        $grouped = array();
        foreach ($groups as $signature => $count) {
            $grouped[] = array(
                'signature' => $signature,
                'count' => $count,
            );
        }

        return array(
            'class_name' => $class_name,
            'nodes_found' => count($rows),
            'groups' => $grouped,
            'is_uniform' => count($groups) <= 1,
        );
    }

    protected static function extract_style_value(string $style, string $variable): string
    {
        if (preg_match('/' . preg_quote($variable, '/') . '\s*:\s*([^;]+);/i', $style, $match)) {
            return trim($match[1]);
        }
        return '';
    }
}

