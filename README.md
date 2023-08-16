# Tag Linker - WordPress Plugin

### Descrição:
Este script PHP cria um plugin para WordPress chamado "Tag Linker" que substitui automaticamente as palavras-chaves no conteúdo do post por links de outros posts relacionados com base em tags definidas.

A fim de garantir que todas as tags inseridas no post sejam distribuídas ao longo do texto, o script verifica a densidade de palavras-chave de cada tag e insere um número proporcional de links para essa tag no conteúdo do post. 

Portanto, se uma tag aparecer com mais frequência no texto, mais links serão inseridos para essa tag, garantindo assim uma distribuição uniforme.

## Funcionalidades:

1. Substituição automática de palavras-chave por links em posts.
2. Cálculo da densidade de palavras-chave para decidir o número de links para uma tag.
3. Plugin é visível no menu de administração do WordPress.

## Uso:

1. Faça o download ou clone este repositório para a pasta de plugins do seu WordPress.
2. No painel de administração, vá para Plugins e ative o Tag Linker.
3. Depois de ativado, você pode acessar o plugin no menu lateral do painel de administração - geralmente abaixo da opção de configurações.

Aqui está uma breve explicação do código:

1. `__construct()`: O método do construtor é chamado quando uma nova instância de TagLinker é criada. Ele adiciona vários métodos à fila de ações do WordPress.

2. `enqueue_select2_scripts()`: Este método registra, enfileira e inline scripts e estilos para o Select2, um plugin jQuery que substitui os seletores padrão HTML por versões mais avançadas.

3. `get_select2_inline_script()`: Este método retorna um script de JavaScript para inicializar o Select2.

4. `calculate_keyword_density()`: Este método calcula a densidade de palavras-chave no conteúdo.

5. `tag_linker_install()`: Este método cria uma tabela no banco de dados durante a ativação do plugin para armazenar o conteúdo original de cada post.

6. `tag_linker_menu()`: Este método adiciona uma página de opções para o Tag Linker no menu de administração.

7. `tag_linker_process_actions()`: Este método processa as ações quando os formulários são enviados nas páginas de opções do Tag Linker.

8. `replace_tags_with_links()`: Este método substitui tags no conteúdo de cada post por links para outros posts com essas tags.

9. `restore_original_content()`: Este método restaura o conteúdo original dos posts que foram modificados pelo Tag Linker.

10. `save_original_content()`, `get_original_content()`, `remove_original_content()`: Estes métodos salvam, recuperam e removem o conteúdo original de cada post no/de do banco de dados, respectivamente.

11. `register_activation_hook()`: Este método registra um gancho a ser executado quando o plugin é ativamente ativado.

## Autor:
Italo Mariano - [Linkedin](https://www.linkedin.com/in/italomsr/)

## Licença:
GPL2
