<?php
/*
Plugin Name: Tag Linker
Plugin URI: https://github.com/italomsr/wp-tag-linker/
Description: Substitui as tags no conteúdo do post com links para outros posts adequados.
Version: 1.0
Author: Italo Mariano
Author URI: https://www.linkedin.com/in/italomsr/
License: GPL2

Description long:
Este script PHP cria um plugin para WordPress chamado "Tag Linker" que substitui automaticamente as palavras-chaves no conteúdo do post por links de outros posts relacionados com base em tags definidas.
Para garantir que todas as tags inseridas no post sejam distribuídas ao longo do texto, o script verifica a densidade de palavras-chave de cada tag e insere um número proporcional de links para essa tag no conteúdo do post. Dessa forma, se uma tag aparecer com mais frequência no texto, mais links serão inseridos para essa tag, garantindo uma distribuição uniforme.
*/

if (!defined('ABSPATH')) {
    exit; // Evita acesso direto ao arquivo
}

class TagLinker
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'tag_linker_menu'));
        add_action('admin_init', array($this, 'tag_linker_process_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_select2_scripts'));
    }

    public function enqueue_select2_scripts()
    {
        // Registrar e enfileirar o estilo do Select2
        wp_register_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');
        wp_enqueue_style('select2');

        // Registrar e enfileirar o script do Select2
        wp_register_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_script('select2');

        // Enfileirar o script personalizado
        wp_add_inline_script('select2', $this->get_select2_inline_script());
    }

    private function get_select2_inline_script()
    {
        return "
            jQuery(document).ready(function() {
                // Inicializar Select2 nos campos 'tag_linker_category' e 'tag_linker_selected_post'
                jQuery('select[name=\"tag_linker_category\"], select[name=\"tag_linker_selected_post\"]').select2({
                    placeholder: 'Selecione uma opção',
                    allowClear: true,
                    width: '300px'
                });
            });
        ";
    }

    public function calculate_keyword_density($content, $keyword)
    {
        $word_count = str_word_count($content);
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $keyword_density = ($keyword_count / $word_count) * 100;

        return $keyword_density;
    }

    function tag_linker_install() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tag_linker_original_content';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            original_content longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function tag_linker_menu() {
        add_submenu_page(
            'options-general.php',
            'Tag Linker',
            'Tag Linker',
            'manage_options',
            'tag-linker',
            array($this, 'tag_linker_options_page')
        );
    }
    
    public function tag_linker_options_page() {
    // Verifique se o usuário tem permissão para acessar a página
    if (!current_user_can('manage_options')) {
        return;
    }

    // Obter todas as categorias e posts
    $categories = get_categories(array(
        'hide_empty' => false,
    ));

    $posts = get_posts(array(
        'posts_per_page' => -1,
        'post_type' => 'any',
    ));

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('tag_linker_action_nonce'); ?>

            <h2>Processar por categoria</h2>
            <select name="tag_linker_category">
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>

            <h2>Ou selecione um post específico</h2>
            <select name="tag_linker_selected_post">
                <option value="">Selecione um post</option>
                <?php foreach ($posts as $post) : ?>
                    <option value="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html(get_the_title($post->ID)); ?></option>
                <?php endforeach; ?>
            </select>

            <p>
                <input type="submit" name="tag_linker_action" value="Inserir Links" class="button button-primary" />
                <input type="submit" name="tag_linker_action" value="Restaurar" class="button" />
            </p>
        </form>
    </div>
    <style>
        .tag-linker-loading {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .tag-linker-loading-spinner {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            border: 16px solid #f3f3f3;
            border-top: 16px solid #3498db;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Estilo do formulário */
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        /* Estilo para os campos de seleção e inputs */
        select[name="tag_linker_category"], select[name="tag_linker_selected_post"], input[type="submit"] {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 6px 12px;
            font-size: 16px;
            line-height: 1.42857143;
            color: #333;
            vertical-align: middle;
            -webkit-appearance: none;
        }

        /* Estilo para os botões */
        input[type="submit"] {
            cursor: pointer;
            margin-top: 10px;
        }

        input[type="submit"].button-primary {
            background-color: #0073aa;
            border-color: #0073aa;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            transition: background-color 0.3s ease;
        }

        input[type="submit"].button-primary:hover {
            background-color: #0095dd;
            border-color: #0095dd;
        }
    </style>
    <div class="tag-linker-loading">
        <div class="tag-linker-loading-spinner"></div>
    </div>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form');
        var loading = document.querySelector('.tag-linker-loading');
        var submitButtons = document.querySelectorAll('input[type="submit"]');

        form.addEventListener('submit', function(event) {
            var selectedCategory = document.querySelector('select[name="tag_linker_category"]').value;
            var selectedPost = document.querySelector('select[name="tag_linker_selected_post"]').value;

            if (selectedCategory === '' && selectedPost === '') {
                var confirmationMessage = 'Nenhuma categoria ou post foi selecionado. Todos os posts serão afetados. Você deseja continuar?';
                var isConfirmed = window.confirm(confirmationMessage);

                if (!isConfirmed) {
                    event.preventDefault();
                    return;
                }
            }

            loading.style.display = 'block';
        });

        submitButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                form.actionButton = this;
            });
        });
    });
    </script>
    <?php
}
    
    public function tag_linker_process_actions()
{
  if (!isset($_POST['tag_linker_action']) || !check_admin_referer('tag_linker_action_nonce')) {
    return;
}

// Obter a categoria e o post selecionados
$selected_category = isset($_POST['tag_linker_category']) ? $_POST['tag_linker_category'] : '';
$selected_post = isset($_POST['tag_linker_selected_post']) ? $_POST['tag_linker_selected_post'] : '';

    // Filtrar os posts com base na categoria ou no post selecionado
    $posts_args = array(
        'posts_per_page' => -1,
        'post_type' => 'any',
    );

    if (!empty($selected_category)) {
        $posts_args['category'] = $selected_category;
    } elseif (!empty($selected_post)) {
        $posts_args['post__in'] = array($selected_post);
    }

// Obter todos os posts
$posts = get_posts($posts_args);

   // Verificar se o post selecionado possui tags
   if (!empty($selected_post)) {
    $tags = get_the_tags($selected_post);
    if (!$tags) {
        // Exibir uma mensagem de aviso
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php _e('Tag Linker: The selected post has no tags.', 'tag-linker'); ?></p>
            </div>
            <?php
        });
        return;
    }
}

if ($_POST['tag_linker_action'] === 'Inserir Links') {
    // Apply tag replacement to all posts
    foreach ($posts as $post) {
        $this->replace_tags_with_links($post->ID);
    }

    // Display a success message
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Tag Linker: changes have been applied successfully.', 'tag-linker'); ?></p>
        </div>
        <?php
    });

} elseif ($_POST['tag_linker_action'] === 'Restaurar') {
    // Restore the original content of all posts
    foreach ($posts as $post) {
        $this->restore_original_content($post->ID);
    }

    // Display a success message
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Tag Linker: original content has been restored successfully.', 'tag-linker'); ?></p>
        </div>
        <?php
    });
}
}

public function replace_tags_with_links($post_id)
{
    $post = get_post($post_id);
    $content = $post->post_content;

    // Verificar se o post possui tags
    $tags = get_the_tags($post_id);

    if (!$tags) {
        return $content;
    }

    // Obter o ID do post atual
    $current_post_id = get_the_ID();

    // Array para armazenar os links já usados
    $used_links = array();

    // Loop em todas as tags
    foreach ($tags as $index => $tag) {
        // Calcular a densidade de palavras-chave para a tag atual
        $keyword_density = $this->calculate_keyword_density($content, $tag->name);

        // Determine a quantidade proporcional de links que devem ser inseridos para a tag atual
        $links_count = ceil($keyword_density / 100);

        for ($i = 0; $i < $links_count; $i++) {
            // Obter posts relacionados com base na tag, excluindo o post atual
            $related_posts = get_posts(array(
                'tag_id' => $tag->term_id,
                'posts_per_page' => -1,
                'post__not_in' => array($post_id),
                'orderby' => 'rand',
                'post_type' => 'any',
            ));

            // Filtrar posts cujo link já foi usado
            $related_posts = array_filter($related_posts, function ($post) use ($used_links) {
                return !in_array($post->ID, $used_links);
            });

            // Se houver um post relacionado, insira o link no conteúdo
            if (!empty($related_posts) && isset($related_posts[0]) && $related_posts[0]->ID != $current_post_id) {
                $related_post = $related_posts[0];

                // Verificar se o link já foi inserido no conteúdo
                if (strpos($content, get_permalink($related_post->ID)) === false) {
                    // Adicionar o link usado ao array $used_links
                    $used_links[] = $related_post->ID;

                    $search_pattern = '/(?<!href=")' . preg_quote($tag->name, '/') . '(?![^<]*<\/a>)/iu';
                    $replacement = sprintf('<a href="%s" class="tag-linker">%s</a>', get_permalink($related_post->ID), $tag->name);

                    // Limitar a substituição a apenas uma ocorrência da tag
                    $content = preg_replace($search_pattern, $replacement, $content, 1);
                }
            }
        }
    }

    // Salve o conteúdo original no banco de dados
    $this->save_original_content($post_id, $post->post_content);

    // Atualize o objeto $post com o novo conteúdo
    $post->post_content = $content;

    // Atualize o post com o novo conteúdo
    wp_update_post($post);
}

public function restore_original_content($post_id)
{
    $original_content = $this->get_original_content($post_id);
    if ($original_content) {
        // Get the post object
        $post = get_post($post_id);

        $search_pattern = '/<a\s+[^>]*class="tag-linker"[^>]*>(.*?)<\/a>/i';
        $replacement = '$1';

        $restored_content = preg_replace($search_pattern, $replacement, $original_content);

        // Update the post with the restored content
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $restored_content
        ));

        // Remove the original content from the database
        $this->remove_original_content($post_id);
    } else {
        // If there is no original_content saved, it means the post was not processed by Tag Linker
        // So, we just remove the links generated by Tag Linker from the current post content
        $post = get_post($post_id);
        $search_pattern = '/<a\s+[^>]*class="tag-linker"[^>]*>(.*?)<\/a>/i';
        $replacement = '$1';
        $restored_content = preg_replace($search_pattern, $replacement, $post->post_content);

        // Update the post with the restored content
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $restored_content
        ));
    }
}
public function save_original_content($post_id, $content)
{
global $wpdb;
$table_name = $wpdb->prefix . 'tag_linker_original_content';
$wpdb->replace($table_name, array(
'post_id' => $post_id,
'original_content' => $content), array(
  '%d',
  '%s'
));
}

public function get_original_content($post_id)
{
global $wpdb;
$table_name = $wpdb->prefix . 'tag_linker_original_content';
$result = $wpdb->get_row($wpdb->prepare(
"SELECT original_content FROM $table_name WHERE post_id = %d",
$post_id
));

return $result ? $result->original_content : false;

}

public function remove_original_content($post_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'tag_linker_original_content';
  $wpdb->delete($table_name, array('post_id' => $post_id), array('%d'));
}

}
$tag_linker = new TagLinker();

// Hook de ativação do plugin
register_activation_hook(__FILE__, array($tag_linker, 'tag_linker_install'));
