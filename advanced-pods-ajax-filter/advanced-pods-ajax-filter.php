<?php
/**
 * Plugin Name: Advanced Pods AJAX Filter
 * Description: V14 - Custom AJAX filter for Pods with specialized CSS and Logic.
 * Version: 14.0
 * Author: Jules
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue Scripts and Styles
 */
function apaf_enqueue_assets() {
    wp_enqueue_style( 'apaf-style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), '14.0' );
    wp_enqueue_script( 'apaf-script', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), '14.0', true );

    wp_localize_script( 'apaf-script', 'apaf_vars', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'apaf_nonce' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'apaf_enqueue_assets' );

/**
 * Shortcode Registration
 * Usage: [advanced_pods_filter]
 */
function apaf_shortcode() {
    ob_start();
    ?>
    <div class="apaf-wrapper">
        <form id="apaf-search-bar">

            <!-- Toggle Group -->
            <div class="toggle-group">
                <label class="switch">
                    <input type="checkbox" name="tipo_negocio" value="venda" checked>
                    <span class="slider"></span>
                    <span class="label-text">Comprar / Alugar</span>
                </label>
            </div>

            <!-- City Select -->
            <select name="cidade" class="apaf-input city-select">
                <option value="0">Todas Cidades</option>
                <!-- Populated dynamically or via PHP in real scenario -->
                <option value="Sao Paulo">São Paulo</option>
                <option value="Rio de Janeiro">Rio de Janeiro</option>
            </select>

            <!-- Neighborhood Select -->
             <select name="bairro" class="apaf-input neighborhood-select">
                <option value="0">Todos Bairros</option>
                <option value="Centro">Centro</option>
                <option value="Jardins">Jardins</option>
            </select>

            <!-- More Filters / Search Button -->
            <button type="button" id="apaf-more-filters-btn">Filtros</button>

        </form>

        <!-- Filters Modal -->
        <div id="apaf-modal" style="display:none;">
            <div class="apaf-modal-backdrop"></div>
            <div class="apaf-modal-content">
                <div class="apaf-modal-header">
                    <h3>Filtros Avançados</h3>
                    <button type="button" class="close-modal">&times;</button>
                </div>
                <div class="apaf-modal-body">

                    <div class="filter-section">
                        <h4>Quartos</h4>
                        <div class="apaf-numeric-buttons" data-key="quartos">
                            <button type="button" data-value="0" class="active">Todos</button>
                            <button type="button" data-value="1">1</button>
                            <button type="button" data-value="2">2</button>
                            <button type="button" data-value="3">3</button>
                            <button type="button" data-value="4+">4+</button>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h4>Aceita Financiamento?</h4>
                         <div class="apaf-numeric-buttons" data-key="aceita_financiamento">
                            <button type="button" data-value="">Todos</button>
                            <button type="button" data-value="1">Sim</button>
                            <button type="button" data-value="0">Não</button>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h4>Rua</h4>
                        <input type="text" name="rua" placeholder="Digite o nome da rua" class="apaf-full-input">
                    </div>

                     <div class="filter-section">
                        <h4>Preço de Venda</h4>
                        <div class="price-inputs">
                            <input type="number" name="preco_min" placeholder="Mínimo">
                            <input type="number" name="preco_max" placeholder="Máximo">
                        </div>
                    </div>

                </div>
                <div class="apaf-modal-footer">
                    <button type="button" id="apaf-apply-filters">Aplicar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Results Container -->
        <div id="apaf-results">
            <!-- AJAX Results will appear here -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'advanced_pods_filter', 'apaf_shortcode' );

/**
 * AJAX Handler
 */
add_action( 'wp_ajax_apaf_filter_pods', 'apaf_filter_pods' );
add_action( 'wp_ajax_nopriv_apaf_filter_pods', 'apaf_filter_pods' );

function apaf_filter_pods() {
    // Security check
    check_ajax_referer( 'apaf_nonce', 'nonce' );

    // Basic sanitization
    $cidade = isset($_POST['cidade']) ? sanitize_text_field($_POST['cidade']) : '0';
    $bairro = isset($_POST['bairro']) ? sanitize_text_field($_POST['bairro']) : '0';
    $quartos = isset($_POST['quartos']) ? sanitize_text_field($_POST['quartos']) : '0';
    $rua = isset($_POST['rua']) ? sanitize_text_field($_POST['rua']) : '';
    $financiamento = isset($_POST['aceita_financiamento']) ? sanitize_text_field($_POST['aceita_financiamento']) : '';
    $preco_min = isset($_POST['preco_min']) ? sanitize_text_field($_POST['preco_min']) : '';
    $preco_max = isset($_POST['preco_max']) ? sanitize_text_field($_POST['preco_max']) : '';

    // Query Arguments
    $args = array(
        'post_type'      => 'imovel', // Adjust custom post type name if different
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
        ),
    );

    // City Logic (0 = All)
    if ( $cidade !== '0' && !empty($cidade) ) {
        $args['meta_query'][] = array(
            'key'     => 'cidade',
            'value'   => $cidade,
            'compare' => '='
        );
    }

    // Neighborhood Logic (0 = All)
    if ( $bairro !== '0' && !empty($bairro) ) {
        $args['meta_query'][] = array(
            'key'     => 'bairro',
            'value'   => $bairro,
            'compare' => '='
        );
    }

    // Bedrooms Logic
    if ( $quartos !== '0' && !empty($quartos) ) {
        if ( strpos($quartos, '+') !== false ) {
            $val = (int)$quartos;
            $args['meta_query'][] = array(
                'key'     => 'quartos',
                'value'   => $val,
                'compare' => '>='
            );
        } else {
            $args['meta_query'][] = array(
                'key'     => 'quartos',
                'value'   => $quartos,
                'compare' => '='
            );
        }
    }

    // Street Logic
    if ( !empty($rua) ) {
        $args['meta_query'][] = array(
            'key'     => 'rua',
            'value'   => $rua,
            'compare' => 'LIKE'
        );
    }

    // Financing Logic
    if ( $financiamento !== '' ) {
        $args['meta_query'][] = array(
            'key'     => 'aceita_financiamento',
            'value'   => $financiamento,
            'compare' => '='
        );
    }

    // Price Logic (preco_venda)
    if ( !empty($preco_min) ) {
         $args['meta_query'][] = array(
            'key'     => 'preco_venda',
            'value'   => $preco_min,
            'type'    => 'NUMERIC',
            'compare' => '>='
        );
    }
    if ( !empty($preco_max) ) {
         $args['meta_query'][] = array(
            'key'     => 'preco_venda',
            'value'   => $preco_max,
            'type'    => 'NUMERIC',
            'compare' => '<='
        );
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        echo '<div class="apaf-grid">';
        while ( $query->have_posts() ) {
            $query->the_post();
            // Basic Output
            ?>
            <div class="apaf-item">
                <h3><?php the_title(); ?></h3>
                <div class="apaf-details">
                    <p>Cidade: <?php echo get_post_meta(get_the_ID(), 'cidade', true); ?></p>
                    <p>Bairro: <?php echo get_post_meta(get_the_ID(), 'bairro', true); ?></p>
                    <p>Preço: <?php echo get_post_meta(get_the_ID(), 'preco_venda', true); ?></p>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p class="apaf-no-results">Nenhum imóvel encontrado.</p>';
    }

    wp_reset_postdata();
    die();
}
