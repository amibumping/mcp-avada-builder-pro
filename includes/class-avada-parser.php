<?php

/**
 * Advanced Shortcode Parser for Avada Fusion Builder
 * Handles nested shortcodes properly with full hierarchy support
 *
 * v4.0.0 Changes:
 * - P1-1: Passthrough pattern preserves unregistered element types
 * - P1-2: Inner row/column support (fusion_builder_row_inner, fusion_builder_column_inner)
 * - Attribute escaping refined to avoid base64 corruption
 */

class MCP_Avada_Parser
{

    private $element_patterns = array();

    /**
     * Layout tags that should not be treated as content elements.
     */
    private $layout_tags = array(
        'fusion_builder_container',
        'fusion_builder_row',
        'fusion_builder_column',
        'fusion_builder_row_inner',
        'fusion_builder_column_inner',
        'fusion_builder_next_page',
    );

    public function __construct()
    {
        $this->init_element_patterns();
    }

    private function init_element_patterns()
    {
        $this->element_patterns = array(
            'fusion_text' => '/\[fusion_text([^\]]*)\](.*?)\[\/fusion_text\]/s',
            // P2-1: fusion_image* patterns must be mutually exclusive via word boundary.
            // fusion_imageframe and fusion_images are matched FIRST (longer prefixes),
            // fusion_image uses a negative lookahead to exclude them.
            'fusion_imageframe' => '/\[fusion_imageframe([^\]]*)\](.*?)\[\/fusion_imageframe\]/s',
            'fusion_images' => '/\[fusion_images([^\]]*)\](.*?)\[\/fusion_images\]/s',
            'fusion_image' => '/\[fusion_image(?!s|frame)([^\]]*)\]/',
            'fusion_button' => '/\[fusion_button([^\]]*)\](.*?)\[\/fusion_button\]/s',
            'fusion_title' => '/\[fusion_title([^\]]*)\](.*?)\[\/fusion_title\]/s',
            'fusion_separator' => '/\[fusion_separator([^\]]*)\]/',
            'fusion_gallery' => '/\[fusion_gallery([^\]]*)\]/',
            'fusion_video' => '/\[fusion_video([^\]]*)\](.*?)\[\/fusion_video\]/s',
            'fusion_audio' => '/\[fusion_audio([^\]]*)\](.*?)\[\/fusion_audio\]/s',
            'fusion_map' => '/\[fusion_map([^\]]*)\](.*?)\[\/fusion_map\]/s',
            'fusion_fontawesome' => '/\[fusion_fontawesome([^\]]*)\]/',
            'fusion_google_fonts' => '/\[fusion_google_fonts([^\]]*)\]/',
            // P2-1: fusion_code_block must be matched before fusion_code
            'fusion_code_block' => '/\[fusion_code_block([^\]]*)\]/',
            'fusion_code' => '/\[fusion_code(?!_block)([^\]]*)\](.*?)\[\/fusion_code\]/s',
            'fusion_alert' => '/\[fusion_alert([^\]]*)\](.*?)\[\/fusion_alert\]/s',
            'fusion_accordion' => '/\[fusion_accordion([^\]]*)\](.*?)\[\/fusion_accordion\]/s',
            'fusion_toggle' => '/\[fusion_toggle([^\]]*)\](.*?)\[\/fusion_toggle\]/s',
            // P2-1: fusion_tabs must be matched before fusion_tab (prefix collision)
            'fusion_tabs' => '/\[fusion_tabs([^\]]*)\](.*?)\[\/fusion_tabs\]/s',
            'fusion_tab' => '/\[fusion_tab(?!s|l)([^\]]*)\](.*?)\[\/fusion_tab\]/s',
            // P2-1: fusion_tooltip must be matched separately (tab prefix)
            'fusion_tooltip' => '/\[fusion_tooltip([^\]]*)\](.*?)\[\/fusion_tooltip\]/s',
            'fusion_popover' => '/\[fusion_popover([^\]]*)\](.*?)\[\/fusion_popover\]/s',
            // P2-1: fusion_modal_text_link before fusion_modal
            'fusion_modal_text_link' => '/\[fusion_modal_text_link([^\]]*)\]/',
            'fusion_modal' => '/\[fusion_modal(?!_text_link)([^\]]*)\](.*?)\[\/fusion_modal\]/s',
            'fusion_pricing_table' => '/\[fusion_pricing_table([^\]]*)\](.*?)\[\/fusion_pricing_table\]/s',
            'fusion_pricing_column' => '/\[fusion_pricing_column([^\]]*)\](.*?)\[\/fusion_pricing_column\]/s',
            'fusion_pricing_price' => '/\[fusion_pricing_price([^\]]*)\](.*?)\[\/fusion_pricing_price\]/s',
            'fusion_pricing_button' => '/\[fusion_pricing_button([^\]]*)\]/',
            // P2-1: fusion_counters before fusion_counter (prefix collision)
            'fusion_counters' => '/\[fusion_counters([^\]]*)\](.*?)\[\/fusion_counters\]/s',
            'fusion_counter' => '/\[fusion_counter(?!s)([^\]]*)\](.*?)\[\/fusion_counter\]/s',
            'fusion_progress' => '/\[fusion_progress([^\]]*)\](.*?)\[\/fusion_progress\]/s',
            // P2-1: fusion_testimonials before fusion_testimonial (prefix collision)
            'fusion_testimonials' => '/\[fusion_testimonials([^\]]*)\](.*?)\[\/fusion_testimonials\]/s',
            'fusion_testimonial' => '/\[fusion_testimonial(?!s)([^\]]*)\](.*?)\[\/fusion_testimonial\]/s',
            'fusion_blog' => '/\[fusion_blog([^\]]*)\]/',
            // P2-1: fusion_portfolio_masonry before fusion_portfolio (prefix collision)
            'fusion_portfolio_masonry' => '/\[fusion_portfolio_masonry([^\]]*)\]/',
            'fusion_portfolio' => '/\[fusion_portfolio(?!_masonry)([^\]]*)\]/',
            'fusion_faq' => '/\[fusion_faq([^\]]*)\](.*?)\[\/fusion_faq\]/s',
            'fusion_checklist' => '/\[fusion_checklist([^\]]*)\](.*?)\[\/fusion_checklist\]/s',
            // P2-1: fusion_content_boxes before fusion_content_box (prefix collision)
            'fusion_content_boxes' => '/\[fusion_content_boxes([^\]]*)\](.*?)\[\/fusion_content_boxes\]/s',
            'fusion_content_box' => '/\[fusion_content_box(?!es)([^\]]*)\](.*?)\[\/fusion_content_box\]/s',
            'fusion_social_links' => '/\[fusion_social_links([^\]]*)\](.*?)\[\/fusion_social_links\]/s',
            'fusion_person' => '/\[fusion_person([^\]]*)\](.*?)\[\/fusion_person\]/s',
            // P2-1: Slider variants - match longer prefixes first
            'fusion_post_slider' => '/\[fusion_post_slider([^\]]*)\]/',
            'fusion_fusion_slider' => '/\[fusion_fusion_slider([^\]]*)\]/',
            'fusion_slider' => '/\[fusion_slider(?!_)([^\]]*)\](.*?)\[\/fusion_slider\]/s',
            'fusion_rev_slider' => '/\[fusion_rev_slider([^\]]*)\]/',
            'fusion_layerslider' => '/\[fusion_layerslider([^\]]*)\]/',
            'fusion_post_grid' => '/\[fusion_post_grid([^\]]*)\]/',
            // P2-1: fusion_tagline before fusion_table (no collision, but table/tagline share prefix)
            'fusion_table' => '/\[fusion_table([^\]]*)\](.*?)\[\/fusion_table\]/s',
            'fusion_tagline' => '/\[fusion_tagline([^\]]*)\](.*?)\[\/fusion_tagline\]/s',
            'fusion_highlight' => '/\[fusion_highlight([^\]]*)\](.*?)\[\/fusion_highlight\]/s',
            'fusion_dropcap' => '/\[fusion_dropcap([^\]]*)\](.*?)\[\/fusion_dropcap\]/s',
            'fusion_woo_products' => '/\[fusion_woo_products([^\]]*)\]/',
            'fusion_woo_cart' => '/\[fusion_woo_cart([^\]]*)\]/',
            'fusion_woo_shortcodes' => '/\[fusion_woo_shortcodes([^\]]*)\]/',
            'fusion_syntax_highlighter' => '/\[fusion_syntax_highlighter([^\]]*)\](.*?)\[\/fusion_syntax_highlighter\]/s',
            'fusion_viewport' => '/\[fusion_viewport([^\]]*)\]/',
        );
    }

    public function parse_nested_shortcode($content, $tag)
    {
        $results = array();
        $open_tag = '[' . $tag;
        $close_tag = '[/' . $tag . ']';
        $tag_len = strlen($tag);

        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $open_pos = strpos($content, $open_tag, $pos);

            if ($open_pos === false) {
                break;
            }

            // Check the character after the tag name - must be ] or space/attr
            $next_char_pos = $open_pos + strlen($open_tag);
            if ($next_char_pos < $len) {
                $next_char = $content[$next_char_pos];
                // Only match if followed by ] or whitespace (not _ or alphanumeric)
                if (!in_array($next_char, array(' ', ']', '\t', '\n', '\r'))) {
                    $pos = $open_pos + 1;
                    continue;
                }
            }

            $attr_start = strpos($content, ']', $open_pos);
            if ($attr_start === false) {
                $pos = $open_pos + 1;
                continue;
            }

            $depth = 1;
            $search_pos = $attr_start + 1;
            $found_close = false;

            while ($search_pos < $len && $depth > 0) {
                $next_open = strpos($content, $open_tag, $search_pos);
                $next_close = strpos($content, $close_tag, $search_pos);

                if ($next_close === false) {
                    break;
                }

                // Check for nested opening tag with same logic
                if ($next_open !== false && $next_open < $next_close) {
                    // Check if this is a valid nested tag
                    $nested_next = $next_open + strlen($open_tag);
                    if ($nested_next < $len) {
                        $nested_char = $content[$nested_next];
                        if (in_array($nested_char, array(' ', ']', '\t', '\n', '\r'))) {
                            $depth++;
                        }
                    }
                    if ($depth > 1) {
                        $search_pos = $next_open + 1;
                        continue;
                    }
                }

                $depth--;
                if ($depth === 0) {
                    $attr_str = substr($content, $open_pos + strlen($open_tag), $attr_start - $open_pos - strlen($open_tag));
                    $inner = substr($content, $attr_start + 1, $next_close - $attr_start - 1);
                    $results[] = array(1 => $attr_str, 2 => $inner);
                    $found_close = true;
                }
                $search_pos = $next_close + 1;
            }

            if ($found_close) {
                $pos = $next_close + 1;
            } else {
                $pos = $open_pos + 1;
            }
        }

        return $results;
    }

    public function parse($content, $include_content = true)
    {
        $structure = array(
            'containers' => array(),
            'raw_content' => $content,
        );

        if (empty(trim($content))) {
            return $structure;
        }

        // P2-2: Use recursive parse_nested_shortcode instead of non-greedy regex.
        // The old regex (.*?) failed on nested/inner containers and malformed content.
        $container_matches = $this->parse_nested_shortcode($content, 'fusion_builder_container');

        foreach ($container_matches as $cindex => $cmatch) {
            $container = array(
                'id' => 'container_' . $cindex,
                'index' => $cindex,
                'attributes' => $this->parse_attributes($cmatch[1]),
                'rows' => array(),
            );

            if ($include_content) {
                $container['raw_content'] = $cmatch[2];
            }

            $row_matches = $this->parse_nested_shortcode($cmatch[2], 'fusion_builder_row');

            foreach ($row_matches as $rindex => $rmatch) {
                $row = array(
                    'id' => 'row_' . $cindex . '_' . $rindex,
                    'index' => $rindex,
                    'attributes' => $this->parse_attributes($rmatch[1]),
                    'columns' => array(),
                );

                if ($include_content) {
                    $row['raw_content'] = $rmatch[2];
                }

                $col_matches = $this->parse_nested_shortcode($rmatch[2], 'fusion_builder_column');

                foreach ($col_matches as $colindex => $colmatch) {
                    $column = array(
                        'id' => 'column_' . $cindex . '_' . $rindex . '_' . $colindex,
                        'index' => $colindex,
                        'attributes' => $this->parse_attributes($colmatch[1]),
                        'elements' => array(),
                        'inner_rows' => array(),
                        'type' => $this->get_column_type($colmatch[1]),
                    );

                    if ($include_content) {
                        $column['raw_content'] = $colmatch[2];

                        // P1-2: Parse inner rows/columns within this column
                        $inner_row_matches = $this->parse_nested_shortcode($colmatch[2], 'fusion_builder_row_inner');

                        if (!empty($inner_row_matches)) {
                            foreach ($inner_row_matches as $ir_index => $ir_match) {
                                $inner_row = array(
                                    'id' => 'inner_row_' . $ir_index,
                                    'index' => $ir_index,
                                    'attributes' => $this->parse_attributes($ir_match[1]),
                                    'inner_columns' => array(),
                                );

                                $inner_col_matches = $this->parse_nested_shortcode($ir_match[2], 'fusion_builder_column_inner');

                                foreach ($inner_col_matches as $ic_index => $ic_match) {
                                    $inner_column = array(
                                        'id' => 'inner_column_' . $ic_index,
                                        'index' => $ic_index,
                                        'attributes' => $this->parse_attributes($ic_match[1]),
                                        'type' => $this->get_column_type($ic_match[1]),
                                        'elements' => $this->parse_elements(
                                            $ic_match[2],
                                            $cindex,
                                            $rindex,
                                            $colindex . '_inner_' . $ir_index . '_' . $ic_index
                                        ),
                                    );

                                    $inner_row['inner_columns'][] = $this->seal($inner_column, 'fusion_builder_column_inner', $ic_match[1], $ic_match[2], count($inner_column['elements']));
                                }

                                $column['inner_rows'][] = $this->seal($inner_row, 'fusion_builder_row_inner', $ir_match[1], $ir_match[2], count($inner_row['inner_columns']));
                            }

                            // Parse elements that are NOT inside inner rows
                            // Strip inner row content before parsing elements at this level
                            $col_content_without_inner = $colmatch[2];
                            foreach ($inner_row_matches as $ir_match) {
                                $inner_raw = '[fusion_builder_row_inner' . $ir_match[1] . ']' . $ir_match[2] . '[/fusion_builder_row_inner]';
                                $col_content_without_inner = str_replace($inner_raw, '', $col_content_without_inner);
                            }
                            $column['elements'] = $this->parse_elements($col_content_without_inner, $cindex, $rindex, $colindex);
                        } else {
                            $column['elements'] = $this->parse_elements($colmatch[2], $cindex, $rindex, $colindex);
                        }

                        $column = $this->seal($column, 'fusion_builder_column', $colmatch[1], $colmatch[2], count($column['elements']) + count($column['inner_rows']));
                    }

                    $row['columns'][] = $column;
                }

                $container['rows'][] = $include_content ? $this->seal($row, 'fusion_builder_row', $rmatch[1], $rmatch[2], count($row['columns'])) : $row;
            }

            $structure['containers'][] = $include_content ? $this->seal($container, 'fusion_builder_container', $cmatch[1], $cmatch[2], count($container['rows'])) : $container;
        }

        if ($include_content) {
            $structure['_raw'] = $content;
            $structure['_raw_inner'] = $content;
            $structure['_n'] = count($structure['containers']);
            $structure['_hash'] = $this->fingerprint($structure);
        }

        return $structure;
    }

    private function parse_attributes($attr_string)
    {
        $attributes = array();

        preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attr_string, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }

    private function get_column_type($attr_string)
    {
        $types = array(
            '1_1' => '1/1',
            '1_2' => '1/2',
            '2_3' => '2/3',
            '1_3' => '1/3',
            '3_5' => '3/5',
            '1_4' => '1/4',
            '3_4' => '3/4',
            '1_5' => '1/5',
            '2_5' => '2/5',
            '4_5' => '4/5',
            '1_6' => '1/6',
            '5_6' => '5/6',
        );

        foreach ($types as $key => $label) {
            if (strpos($attr_string, 'type="' . $key . '"') !== false) {
                return $label;
            }
        }

        return '1/1';
    }

    /**
     * Parse elements from column content.
     *
     * P1-1: Uses a two-pass approach:
     *  1. Match all known element patterns
     *  2. Scan for ANY remaining shortcodes not matched and preserve them as passthrough
     *
     * This prevents silent data loss for unregistered element types.
     */
    public function parse_elements($content, $cindex = 0, $rindex = 0, $colindex = 0)
    {
        $elements = array();
        $matched_ranges = array(); // Track byte ranges that were matched by known patterns
        $fusion_images_ranges = array(); // Track fusion_images block ranges to avoid nested fusion_image duplication

        // Pre-scan fusion_images blocks so nested [fusion_image] items are not parsed as standalone elements.
        if (isset($this->element_patterns['fusion_images'])) {
            preg_match_all($this->element_patterns['fusion_images'], $content, $fusion_images_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            foreach ($fusion_images_matches as $fmatch) {
                $foffset = $fmatch[0][1];
                $flength = strlen($fmatch[0][0]);
                $fusion_images_ranges[] = array($foffset, $foffset + $flength);
            }
        }

        // PASS 1: Match all KNOWN element types
        foreach ($this->element_patterns as $type => $pattern) {
            // Skip layout tags - they are handled at the container/row/column level
            if (in_array($type, $this->layout_tags, true) || strpos($type, 'fusion_builder_') === 0) {
                continue;
            }

            // Prefer WordPress' own shortcode grammar: it is exactly how the page is rendered,
            // so tag boundaries ("fusion_gallery" vs "fusion_gallery_image") and enclosing vs
            // self-closing forms match the front end. The static pattern is only a fallback.
            $use_wp_grammar = function_exists('get_shortcode_regex');
            $regex = $use_wp_grammar ? '/' . get_shortcode_regex(array($type)) . '/s' : $pattern;

            preg_match_all($regex, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

            foreach ($matches as $match) {
                $offset = $match[0][1];
                $length = strlen($match[0][0]);
                $end = $offset + $length;

                if ($use_wp_grammar) {
                    // [[tag]] is an escaped shortcode, rendered literally.
                    if ('[' === $match[1][0] && ']' === ($match[6][0] ?? '')) {
                        continue;
                    }
                    $attr_string = $match[3][0];
                    $inner = (isset($match[5]) && -1 !== $match[5][1]) ? $match[5][0] : '';
                } else {
                    // The static pattern only anchors the tag prefix, so "[fusion_gallery" also
                    // matches "[fusion_gallery_image ...]"; a longer tag name means another element.
                    if (isset($match[1][0]) && preg_match('/^[a-zA-Z0-9_-]/', $match[1][0])) {
                        continue;
                    }
                    $attr_string = $match[1][0];
                    $inner = isset($match[2]) ? $match[2][0] : '';
                }

                // Guard: do not parse [fusion_image] shortcodes that belong to a [fusion_images] block.
                if ('fusion_image' === $type) {
                    $inside_fusion_images = false;
                    foreach ($fusion_images_ranges as $frange) {
                        if ($offset >= $frange[0] && $end <= $frange[1]) {
                            $inside_fusion_images = true;
                            break;
                        }
                    }
                    if ($inside_fusion_images) {
                        continue;
                    }
                }

                $raw = $match[0][0];
                $elements[] = array(
                    'type' => $type,
                    'attributes' => $this->parse_attributes($attr_string),
                    'content' => $inner,
                    '_offset' => $offset,
                    '_length' => $length,
                    '_raw' => $raw,
                    '_closed' => substr($raw, -strlen('[/' . $type . ']')) === '[/' . $type . ']',
                );

                $matched_ranges[] = array($offset, $offset + $length);
            }
        }

        // Keep only outermost matches: a known element nested inside another one
        // (e.g. [fusion_toggle] inside [fusion_accordion]) already travels inside its
        // parent's content and must not be emitted again as a standalone element.
        $outermost = array();
        foreach ($elements as $i => $candidate) {
            $c_start = $candidate['_offset'];
            $c_end = $c_start + $candidate['_length'];
            $nested = false;
            foreach ($elements as $j => $other) {
                if ($i === $j) {
                    continue;
                }
                $o_start = $other['_offset'];
                $o_end = $o_start + $other['_length'];
                $contains = $o_start <= $c_start && $c_end <= $o_end;
                $same_span = $o_start === $c_start && $o_end === $c_end;
                if ($contains && (!$same_span || $j < $i)) {
                    $nested = true;
                    break;
                }
            }
            if (!$nested) {
                $outermost[] = $candidate;
            }
        }
        $elements = $outermost;
        $matched_ranges = array();
        foreach ($elements as $element) {
            $matched_ranges[] = array($element['_offset'], $element['_offset'] + $element['_length']);
        }

        // PASS 2: Find ANY shortcodes that were NOT matched by known patterns
        // This catches unregistered element types and preserves them as raw passthrough
        // Matches both [tag attrs]content[/tag] and self-closing [tag attrs]
        $all_shortcode_pattern = '/\[([a-zA-Z_][a-zA-Z0-9_-]*)([^\]]*)\](?:((?:(?!\[\1[^\]]*\]).)*?)\[\/\1\])?/s';
        preg_match_all($all_shortcode_pattern, $content, $all_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($all_matches as $match) {
            $tag_name = $match[1][0];
            $offset = $match[0][1];
            $length = strlen($match[0][0]);

            // Skip layout tags
            if (in_array($tag_name, $this->layout_tags, true)) {
                continue;
            }

            // Skip if this range was already matched by a known pattern
            $already_matched = false;
            foreach ($matched_ranges as $range) {
                if ($offset >= $range[0] && $offset < $range[1]) {
                    $already_matched = true;
                    break;
                }
            }
            if ($already_matched) {
                continue;
            }

            // This is an unregistered/unknown element - preserve as raw passthrough
            $elements[] = array(
                'type' => '__passthrough__',
                'raw_shortcode' => $match[0][0],
                'shortcode_tag' => $tag_name,
                'attributes' => array(),
                'content' => '',
                '_offset' => $offset,
                '_length' => $length,
                '_raw' => $match[0][0],
            );

            $matched_ranges[] = array($offset, $offset + $length);
        }

        // Sort by position in source content to preserve document order
        usort($elements, function ($a, $b) {
            return $a['_offset'] - $b['_offset'];
        });

        // Assign IDs and paths after sorting
        foreach ($elements as $index => &$element) {
            $type_label = $element['type'] === '__passthrough__' ? $element['shortcode_tag'] : $element['type'];
            $element['id'] = $type_label . '_' . $index;
            $element['path'] = "container_{$cindex}/row_{$rindex}/column_{$colindex}/{$element['id']}";
            unset($element['_offset']);
            unset($element['_length']);
            $element['_hash'] = $this->fingerprint($element);
        }
        unset($element);

        return $elements;
    }

    public function extract_all_elements($structure)
    {
        $elements = array();

        foreach ($structure['containers'] as $cindex => $container) {
            foreach ($container['rows'] as $rindex => $row) {
                foreach ($row['columns'] as $colindex => $column) {
                    // Elements directly in the column
                    foreach ($column['elements'] as $element) {
                        $element['path'] = "container_{$cindex}/row_{$rindex}/column_{$colindex}/{$element['id']}";
                        $element['container_index'] = $cindex;
                        $element['row_index'] = $rindex;
                        $element['column_index'] = $colindex;
                        $elements[] = $element;
                    }

                    // P1-2: Elements inside inner rows/columns
                    if (!empty($column['inner_rows'])) {
                        foreach ($column['inner_rows'] as $ir_index => $inner_row) {
                            if (!empty($inner_row['inner_columns'])) {
                                foreach ($inner_row['inner_columns'] as $ic_index => $inner_col) {
                                    foreach ($inner_col['elements'] as $element) {
                                        $element['container_index'] = $cindex;
                                        $element['row_index'] = $rindex;
                                        $element['column_index'] = $colindex;
                                        $element['inner_row_index'] = $ir_index;
                                        $element['inner_column_index'] = $ic_index;
                                        $elements[] = $element;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $elements;
    }

    public function generate($structure)
    {
        if (!isset($structure['containers'])) {
            return '';
        }

        // Lossless path: untouched content is written back byte for byte.
        if ($this->is_pristine($structure)) {
            return $structure['_raw'];
        }

        $spliced = $this->splice($structure, array(
            array($structure['containers'], function ($container) {
                return $this->generate_container($container);
            }),
        ));
        if (null !== $spliced) {
            return $spliced;
        }

        $content = '';
        foreach ($structure['containers'] as $container) {
            $content .= $this->generate_container($container);
        }
        return $content;
    }

    private function generate_container($container)
    {
        $rows = isset($container['rows']) ? $container['rows'] : array();
        $render = function ($row) {
            return $this->generate_row($row);
        };
        return $this->emit_node($container, 'fusion_builder_container', array(array($rows, $render)), function () use ($rows, $render) {
            return implode('', array_map($render, $rows));
        });
    }

    private function generate_row($row)
    {
        $columns = isset($row['columns']) ? $row['columns'] : array();
        $render = function ($column) {
            return $this->generate_column($column);
        };
        return $this->emit_node($row, 'fusion_builder_row', array(array($columns, $render)), function () use ($columns, $render) {
            return implode('', array_map($render, $columns));
        });
    }

    private function generate_column($column)
    {
        $elements = isset($column['elements']) ? $column['elements'] : array();
        $inner_rows = !empty($column['inner_rows']) ? $column['inner_rows'] : array();
        $render_element = function ($element) {
            return $this->generate_element($element);
        };
        $render_inner_row = function ($inner_row) {
            return $this->generate_inner_row($inner_row);
        };
        return $this->emit_node(
            $column,
            'fusion_builder_column',
            array(array($elements, $render_element), array($inner_rows, $render_inner_row)),
            function () use ($elements, $inner_rows, $render_element, $render_inner_row) {
                // Structural edits cannot keep the original interleaving; elements first, then inner rows.
                return implode('', array_map($render_element, $elements)) . implode('', array_map($render_inner_row, $inner_rows));
            }
        );
    }

    private function generate_inner_row($inner_row)
    {
        $inner_columns = !empty($inner_row['inner_columns']) ? $inner_row['inner_columns'] : array();
        $render = function ($inner_column) {
            $elements = !empty($inner_column['elements']) ? $inner_column['elements'] : array();
            $render_element = function ($element) {
                return $this->generate_element($element);
            };
            return $this->emit_node($inner_column, 'fusion_builder_column_inner', array(array($elements, $render_element)), function () use ($elements, $render_element) {
                return implode('', array_map($render_element, $elements));
            });
        };
        return $this->emit_node($inner_row, 'fusion_builder_row_inner', array(array($inner_columns, $render)), function () use ($inner_columns, $render) {
            return implode('', array_map($render, $inner_columns));
        });
    }

    /**
     * Emit a layout node: verbatim when untouched, otherwise its original text with
     * only the changed children replaced, falling back to a full rebuild.
     */
    private function emit_node(array $node, $tag, array $child_lists, callable $rebuild)
    {
        if ($this->is_pristine($node)) {
            return $node['_raw'];
        }

        $inner = $this->splice($node, $child_lists);
        if (null === $inner) {
            $inner = $rebuild();
        }

        $attributes = isset($node['attributes']) ? $node['attributes'] : array();
        $open = (isset($node['_open'], $node['_attrs_hash']) && md5(serialize($attributes)) === $node['_attrs_hash'])
            ? $node['_open']
            : '[' . $tag . $this->build_attributes($attributes) . ']';

        return $open . $inner . '[/' . $tag . ']';
    }

    /**
     * Rebuild a node's inner text by replacing each child's original text in place.
     *
     * Returns null when that is not safe: a child is new, was moved, reordered,
     * duplicated or deleted, or its original text is ambiguous.
     *
     * @param array $node        Parsed node carrying _raw_inner and _n.
     * @param array $child_lists List of array(children, render callback).
     * @return string|null
     */
    private function splice(array $node, array $child_lists)
    {
        if (!isset($node['_raw_inner'], $node['_n'])) {
            return null;
        }

        $raw = $node['_raw_inner'];
        $items = array();
        foreach ($child_lists as $list) {
            list($children, $render) = $list;
            $last = -1;
            foreach ($children as $child) {
                if (empty($child['_raw']) || 1 !== substr_count($raw, $child['_raw'])) {
                    return null;
                }
                $at = strpos($raw, $child['_raw']);
                if ($at <= $last) {
                    return null;
                }
                $last = $at;
                $items[] = array($at, $child, $render);
            }
        }

        if (count($items) !== (int) $node['_n']) {
            return null;
        }

        usort($items, function ($a, $b) {
            return $a[0] - $b[0];
        });

        $out = '';
        $pos = 0;
        foreach ($items as $item) {
            list($at, $child, $render) = $item;
            if ($at < $pos) {
                return null;
            }
            $out .= substr($raw, $pos, $at - $pos) . $render($child);
            $pos = $at + strlen($child['_raw']);
        }

        return $out . substr($raw, $pos);
    }

    /**
     * Record a node's original text and fingerprint so generate() can reuse it.
     */
    private function seal(array $node, $tag, $attr_string, $inner, $child_count)
    {
        $node['_open'] = '[' . $tag . $attr_string . ']';
        $node['_raw_inner'] = $inner;
        $node['_raw'] = $node['_open'] . $inner . '[/' . $tag . ']';
        $node['_attrs_hash'] = md5(serialize(isset($node['attributes']) ? $node['attributes'] : array()));
        $node['_n'] = $child_count;
        $node['_hash'] = $this->fingerprint($node);
        return $node;
    }

    private function is_pristine($node)
    {
        return is_array($node) && isset($node['_raw'], $node['_hash']) && $this->fingerprint($node) === $node['_hash'];
    }

    /**
     * Hash of a node's content, ignoring positional data and cached source text.
     */
    private function fingerprint($node)
    {
        return md5(serialize($this->semantic_view($node)));
    }

    private function semantic_view($node)
    {
        if (!is_array($node)) {
            return $node;
        }
        $out = array();
        foreach ($node as $key => $value) {
            if (is_string($key) && ('' === $key || '_' === $key[0] || in_array($key, array('id', 'index', 'path', 'canonical_path', 'raw_content'), true))) {
                continue;
            }
            $out[$key] = $this->semantic_view($value);
        }
        return $out;
    }

    /**
     * Remove the private bookkeeping keys (_raw, _hash, ...) before returning a structure to clients.
     */
    public static function strip_private_keys($node)
    {
        if (!is_array($node)) {
            return $node;
        }
        $out = array();
        foreach ($node as $key => $value) {
            if (is_string($key) && '' !== $key && '_' === $key[0] && '__passthrough__' !== $key) {
                continue;
            }
            $out[$key] = self::strip_private_keys($value);
        }
        return $out;
    }

    /**
     * Build shortcode attribute string from associative array.
     *
     * Uses targeted escaping: only escapes double quotes within values
     * to avoid corrupting base64-encoded attribute values.
     */
    private function build_attributes($attributes)
    {
        if (empty($attributes)) {
            return '';
        }

        $attrs = array();
        foreach ($attributes as $key => $value) {
            // Escape only double quotes in values to preserve base64 and special chars
            $escaped_value = str_replace('"', '&quot;', $value);
            $attrs[] = $key . '="' . $escaped_value . '"';
        }

        return ' ' . implode(' ', $attrs);
    }

    /**
     * Generate shortcode text for a single element.
     *
     * P1-1: Handles __passthrough__ elements by outputting raw shortcode text.
     */
    private function generate_element($element)
    {
        // Untouched elements are written back exactly as they were parsed.
        if ($this->is_pristine($element)) {
            return $element['_raw'];
        }

        // P1-1: Passthrough elements are output as raw text (no parsing/modification)
        if ($element['type'] === '__passthrough__' && isset($element['raw_shortcode'])) {
            return $element['raw_shortcode'];
        }

        $type = $element['type'];
        $attrs = $this->build_attributes(isset($element['attributes']) ? $element['attributes'] : array());
        $element_content = isset($element['content']) ? (string) $element['content'] : '';

        // Keep the original form. A dropped closing tag would let the element swallow the
        // following content, so new elements and anything with content are always closed;
        // known self-closing ones use the explicit "/]" form, which WordPress never pairs.
        $closed = isset($element['_closed']) ? ($element['_closed'] || '' !== $element_content) : true;

        if ($closed) {
            return '[' . $type . $attrs . ']' . $element_content . '[/' . $type . ']';
        }
        return '[' . $type . $attrs . ' /]';
    }
}
