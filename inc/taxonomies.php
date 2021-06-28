<?php

class SimpleTags_Admin_Taxonomies
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    // WP_List_Table object
    public $terms_table;

    /**
     * Constructor
     *
     * @return void
     * @author Olatechpro
     */
    public function __construct()
    {

        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);

        // Javascript
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'], 11);

    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author Olatechpro
     */
    public static function admin_enqueue_scripts()
    {
        wp_register_script('st-taxonomies', STAGS_URL . '/assets/js/taxonomies.js',
            ['jquery', 'jquery-ui-dialog', 'postbox'], STAGS_VERSION);
        wp_register_style('st-taxonomies-css', STAGS_URL . '/assets/css/taxonomies.css', ['wp-jquery-ui-dialog'],
            STAGS_VERSION, 'all');

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_taxonomies') {
            wp_enqueue_script('st-taxonomies');
            wp_enqueue_style('st-taxonomies-css');


            $core                  = get_taxonomies(['_builtin' => true]);
            $public                = get_taxonomies([
                '_builtin' => false,
                'public'   => true,
            ]);
            $private               = get_taxonomies([
                '_builtin' => false,
                'public'   => false,
            ]);
            $registered_taxonomies = array_merge($core, $public, $private);
            wp_localize_script('st-taxonomies', 'taxopress_tax_data',
                [
                    'confirm'             => esc_html__('Are you sure you want to delete this? Deleting will NOT remove created content.',
                        'simpletags'),
                    'no_associated_type'  => esc_html__('Please select at least one post type.', 'simpletags'),
                    'existing_taxonomies' => $registered_taxonomies,
                ]
            );

        }
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author Olatechpro
     */
    public function admin_menu()
    {
        $hook = add_submenu_page(
            self::MENU_SLUG,
            __('Taxonomies', 'simpletags'),
            __('Taxonomies', 'simpletags'),
            'simple_tags',
            'st_taxonomies',
            [
                $this,
                'page_manage_taxonomies',
            ]
        );

        add_action("load-$hook", [$this, 'screen_option']);
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => __('Number of items per page', 'simpletags'),
            'default' => 20,
            'option'  => 'st_taxonomies_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Taxonomy_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_taxonomies()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);

        if (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php _e('Taxonomies', 'simpletags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_taxonomies&add=taxonomy')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simpletags'); ?></a>

                   <div class="taxopress-description">This feature allows you to create new taxonomies and edit all the settings for each taxonomy.</div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;',
                            'simpletags') . '</span>', $search);
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Taxonomies', 'simpletags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
<?php 
$selected_option = 'public';
if ( isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'all' ) {
    $selected_option = 'all';
}elseif ( isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'private' ) {
    $selected_option = 'private';
}
?>
<div class="taxopress-taxonomy-type-wrap">
<select name="taxopress-taxonomy-type" class="taxopress-taxonomy-type">
    <option value="all" <?php echo ($selected_option === 'all' ? 'selected="selected"' : ''); ?>><?php echo __('All Taxonomies', 'simpletags'); ?></option>
    <option value="public" <?php echo ($selected_option === 'public' ? 'selected="selected"' : ''); ?>><?php echo __('Public Taxonomies', 'simpletags'); ?></option>
    <option value="private" <?php echo ($selected_option === 'private' ? 'selected="selected"' : ''); ?>><?php echo __('Private Taxonomies', 'simpletags'); ?></option>
</select>
</div>
                        <form action="<?php echo add_query_arg('', '') ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php __('Description here.', 'simpletags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php } else {
            if ($_GET['add'] == 'taxonomy') {
                //add/edit taxonomy
                $this->taxopress_manage_taxonomies();
                echo '<div>';
            }
        } ?>


        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
        do_action('simpletags-taxonomies', SimpleTags_Admin::$taxonomy);
    }


    /**
     * Create our settings page output.
     *
     * @internal
     */
    public function taxopress_manage_taxonomies()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php
        /**
         * Fires right inside the wrap div for the taxonomy editor screen.
         */
        do_action('taxopress_inside_taxonomy_wrap');

        /**
         * Filters whether or not a taxonomy was deleted.
         *
         * @param bool $value Whether or not taxonomy deleted. Default false.
         */
        $taxonomy_deleted = apply_filters('taxopress_taxonomy_deleted', false);

        /**
         * Fires below the output for the tab menu on the taxonomy add/edit screen.
         */
        do_action('taxopress_below_taxonomy_tab_menu');

        $external_edit = false;
        $taxonomy_edit = false;
        $core_edit = false;

        if ('edit' === $tab) {

            $taxonomies = taxopress_get_taxonomy_data();

            $selected_taxonomy = taxopress_get_current_taxonomy($taxonomy_deleted);
            $request_tax       = sanitize_text_field($_GET['taxopress_taxonomy']);

            if ($selected_taxonomy && array_key_exists($selected_taxonomy, $taxonomies)) {
                $current       = $taxonomies[$selected_taxonomy];
                $taxonomy_edit = true;
            } elseif (taxonomy_exists($request_tax)) {
                //not out taxonomy
                $external_taxonomy = get_taxonomies(['name' => $request_tax], 'objects');
                if (isset($external_taxonomy) > 0) {
                    $current       = taxopress_convert_external_taxonomy($external_taxonomy[$request_tax],
                        $request_tax);
                    $external_edit = true;
                    $taxonomy_edit = true;
                }
            }

            if($request_tax === 'media_tag'){
                $external_edit = false;
            }
        }

        if($taxonomy_edit){
            $wordpress_core_tax = array_keys(get_taxonomies(['_builtin' => true]));
            $wordpress_core_tax[] = 'post_tag';
            $wordpress_core_tax[] = 'category';
            if(in_array($current['name'], $wordpress_core_tax)){
                $core_edit = true;
            }
        }

        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Taxonomy', 'simpletags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="<?php echo esc_url(taxopress_get_post_form_action($ui)); ?>">

                <div class="taxopress-right-sidebar">
                    <div class="taxopress-right-sidebar-wrapper">

                        <?php
                        if($taxonomy_edit){
                        if ($core_edit) {
                            echo '<div class="taxopress-warning">' . __('This taxonomy is part of the WordPress core.',
                                    'simpletags') . '

                                                <br /><br />
                                                ' . __('Registration key',
                                    'simpletags') . ': <font color="green">' . $current["name"] . '</font>
                                                
                                                </div>';
                        }elseif ($external_edit) {
                            echo '<div class="taxopress-warning">' . __('This is an external taxonomy and not created with TaxoPress.',
                                    'simpletags') . '

                                                <br /><br />
                                                ' . __('Registration key',
                                    'simpletags') . ': <font color="green">' . $current["name"] . '</font>
                                                
                                                </div>';
                        }else{
                            echo '<div class="taxopress-warning">' . __('This taxonomy was created by TaxoPress.',
                                    'simpletags') . '

                                                <br /><br />
                                                ' . __('Registration key',
                                    'simpletags') . ': <font color="green">' . $current["name"] . '</font>
                                                
                                                </div>';
                        }
                    }
                        ?>

                        <p class="submit">

                            <?php
                            wp_nonce_field('taxopress_addedit_taxonomy_nonce_action',
                                'taxopress_addedit_taxonomy_nonce_field');
                            if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                <?php

                                /**
                                 * Filters the text value to use on the button when editing.
                                 *
                                 * @param string $value Text to use for the button.
                                 */
                                ?>
                                <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                       value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_edit',
                                           esc_attr__('Save Taxonomy', 'simpletags'))); ?>"/>
                                <?php
                            } else { ?>
                            <?php

                            /**
                             * Filters the text value to use on the button when adding.
                             *
                             * @param string $value Text to use for the button.
                             */
                            ?>
                            <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                   value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_add',
                                       esc_attr__('Add Taxonomy', 'simpletags'))); ?>"/>
                        <div class="taxonomy-required-field"></div>
                    <?php } ?>

                        <?php if (!empty($current)) { ?>
                            <input type="hidden" name="tax_original" id="tax_original"
                                   value="<?php echo esc_attr($current['name']); ?>"/>
                            <?php
                        }

                        // Used to check and see if we should prevent duplicate slugs.
                        ?>
                        <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                               value="<?php echo esc_attr($tab); ?>"/>
                        </p>


                    </div>

                </div>

                <?php
                if ($external_edit) {
                    echo '<input type="hidden" name="taxonomy_external_edit" class="taxonomy_external_edit" value="1" />';
                }
                ?>


                <div class="taxonomiesui">


                    <div class="postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($taxonomy_edit) {
                                            echo esc_html__('Edit Taxonomy', 'simpletags');
                                        } else {
                                            echo esc_html__('Add new Taxonomy', 'simpletags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">

                                        <ul class="st-taxonomy-tab">
                                            <li class="taxonomy_general_tab active" data-content="taxonomy_general">
                                                <a href="#taxonomy_general"><span><?php esc_html_e('General',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_posttypes_tab" data-content="taxonomy_posttypes">
                                                <a href="#taxonomy_posttypes"><span><?php esc_html_e('Post Types',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_permalinks_tab" data-content="taxonomy_permalinks">
                                                <a href="#taxonomy_permalinks"><span><?php esc_html_e('Permalinks',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_menus_tab" data-content="taxonomy_menus">
                                                <a href="#taxonomy_menus"><span><?php esc_html_e('Admin Area',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_labels_tab" data-content="taxonomy_labels">
                                                <a href="#taxonomy_labels"><span><?php esc_html_e('Other Labels',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_restapi_tab" data-content="taxonomy_restapi">
                                                <a href="#taxonomy_restapi"><span><?php esc_html_e('REST API',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <li class="taxonomy_advanced_tab" data-content="taxonomy_advanced">
                                                <a href="#taxonomy_advanced"><span><?php esc_html_e('Advanced',
                                                            'simpletags'); ?></span></a>
                                            </li>

                                            <?php if($taxonomy_edit){ ?>
                                            <li class="taxonomy_slug_tab" data-content="taxonomy_slug">
                                                <a href="#taxonomy_slug"><span><?php esc_html_e('Slug',
                                                            'simpletags'); ?></span></a>
                                            </li>
                                            <?php } ?>

                                            <?php if($taxonomy_edit){ ?>
                                            <li class="taxonomy_templates_tab" data-content="taxonomy_templates">
                                                <a href="#taxonomy_templates"><span><?php esc_html_e('Templates',
                                                            'simpletags'); ?></span></a>
                                            </li>
                                            <?php } ?>
                                            
                                            <?php if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                            <li class="taxonomy_delete_tab" data-content="taxonomy_delete">
                                                <a href="#taxonomy_delete"><span><?php esc_html_e('Deactivate or Delete',
                                                            'simpletags'); ?></span></a>
                                            </li>
                                            <?php } ?>
                                        </ul>


                                        <div class="st-taxonomy-content">


                                            <table class="form-table taxopress-table taxonomy_general">
                                                <?php
                                                echo $ui->get_tr_start();

                                                if(!$taxonomy_edit){
                                                    echo $ui->get_th_start();
                                                echo $ui->get_label('name',
                                                        esc_html__('Taxonomy Slug',
                                                            'simpletags')) . $ui->get_required_span();

                                                if ('edit' === $tab) {
                                                    echo '<p id="slugchanged" class="hidemessage">' . esc_html__('Slug has changed',
                                                            'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';
                                                }
                                                echo '<p id="slugexists" class="hidemessage">' . esc_html__('Slug already exists',
                                                        'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';
                                                echo '<p id="st-tags-slug-error-input" class="hidemessage">' . esc_html__('Special character not allowed in slug.', 'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';

                                                echo $ui->get_th_end() . $ui->get_td_start();

                                                echo $ui->get_text_input([
                                                    'namearray'   => 'cpt_custom_tax',
                                                    'name'        => 'name',
                                                    'textvalue'   => isset($current['name']) ? esc_attr($current['name']) : '',
                                                    'maxlength'   => '32',
                                                    'helptext'    => 'Slugs can only contain alphanumeric, latin characters and underscores.',
                                                    'class'     => 'tax-slug-input',
                                                    'required'    => true,
                                                    'placeholder' => false,
                                                    'wrap'        => false,
                                                ]);

                                                if ('edit' === $tab) {
                                                    echo '<p>';
                                                    esc_html_e('DO NOT EDIT the taxonomy slug unless also planning to migrate terms. Changing the slug registers a new taxonomy entry.',
                                                        'simpletags');
                                                    echo '</p>';

                                                    echo '<div class="taxopress-spacer">';
                                                    echo $ui->get_check_input([
                                                        'checkvalue' => 'update_taxonomy',
                                                        'checked'    => 'false',
                                                        'name'       => 'update_taxonomy',
                                                        'namearray'  => 'update_taxonomy',
                                                        'labeltext'  => esc_html__('Migrate terms to newly renamed taxonomy?',
                                                            'simpletags'),
                                                        'helptext'   => '',
                                                        'default'    => false,
                                                        'wrap'       => false,
                                                    ]);
                                                    echo '</div>';
                                                }

                                            }



                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_custom_tax',
                                                    'name'      => 'label',
                                                    'textvalue' => isset($current['label']) ? esc_attr($current['label']) : '',
                                                    'aftertext' => esc_html__('(e.g. Jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('Plural Label', 'simpletags'),
                                                    'helptext'  => '',
                                                    'required'  => true,
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_custom_tax',
                                                    'name'      => 'singular_label',
                                                    'textvalue' => isset($current['singular_label']) ? esc_attr($current['singular_label']) : '',
                                                    'aftertext' => esc_html__('(e.g. Job)', 'simpletags'),
                                                    'labeltext' => esc_html__('Singular Label', 'simpletags'),
                                                    'helptext'  => '',
                                                    'required'  => true,
                                                ]);



                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr'    => '0',
                                                        'text'    => esc_attr__('False', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                    [
                                                        'attr' => '1',
                                                        'text' => esc_attr__('True', 'simpletags'),
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['hierarchical']) : '';
                                            $select['selected'] = !empty($selected) ? $current['hierarchical'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'hierarchical',
                                                'labeltext'  => esc_html__('Parent-Child Relationships', 'simpletags'),
                                                'aftertext'  => esc_html__('Can terms in this taxonomy be organized into hierarchical relationships?',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);



                                                if (isset($current['description'])) {
                                                    $current['description'] = stripslashes_deep($current['description']);
                                                }
                                                echo $ui->get_textarea_input([
                                                    'namearray' => 'cpt_custom_tax',
                                                    'name'      => 'description',
                                                    'rows'      => '4',
                                                    'cols'      => '40',
                                                    'textvalue' => isset($current['description']) ? esc_textarea($current['description']) : '',
                                                    'labeltext' => esc_html__('Description', 'simpletags'),
                                                    'helptext'  => esc_attr__('Describe what your taxonomy is used for.',
                                                        'simpletags'),
                                                ]);
                                        


                                                echo $ui->get_td_end() . $ui->get_tr_end();
                                                ?>
                                            </table>


                                            <table class="form-table taxopress-table taxonomy_posttypes"
                                                   style="display:none;">
                                                <?php

                                                /**
                                                 * Filters the arguments for post types to list for taxonomy association.
                                                 *
                                                 *
                                                 * @param array $value Array of default arguments.
                                                 */
                                                $args = apply_filters('taxopress_attach_post_types_to_taxonomy',
                                                    ['public' => true]);

                                                // If they don't return an array, fall back to the original default. Don't need to check for empty, because empty array is default for $args param in get_post_types anyway.
                                                if (!is_array($args)) {
                                                    $args = ['public' => true];
                                                }
                                                $output = 'objects'; // Or objects.

                                                /**
                                                 * Filters the results returned to display for available post types for taxonomy.
                                                 *
                                                 * @param array $value Array of post type objects.
                                                 * @param array $args Array of arguments for the post type query.
                                                 * @param string $output The output type we want for the results.
                                                 */
                                                $post_types = apply_filters('taxopress_get_post_types_for_taxonomies',
                                                    get_post_types($args, $output), $args, $output);

                                                foreach ($post_types as $post_type) {
                                                    $core_label = in_array($post_type->name, [
                                                        'post',
                                                        'page',
                                                        'attachment',
                                                    ], true) ? '' : '';


                                                echo '<tr valign="top"><th scope="row"><label for="'.$post_type->name.'">'.$post_type->label.'</label></th><td>';
                                                
                                                    echo $ui->get_check_input([
                                                        'checkvalue' => $post_type->name,
                                                        'checked'    => (!empty($current['object_types']) && is_array($current['object_types']) && in_array($post_type->name,
                                                                $current['object_types'], true)) ? 'true' : 'false',
                                                        'name'       => $post_type->name,
                                                        'namearray'  => 'cpt_post_types',
                                                        'textvalue'  => $post_type->name,
                                                        'labeltext'  => "",
                                                        'wrap'       => false,
                                                    ]);
                                                
                                                echo '</td></tr>';

                                            
                                                }


                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr'    => '0',
                                                        'text'    => esc_attr__('False', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                    [
                                                        'attr' => '1',
                                                        'text' => esc_attr__('True', 'simpletags'),
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) && isset($current['include_in_result']) ? taxopress_disp_boolean($current['include_in_result']) : '';
                                            $select['selected'] = !empty($selected) ? $current['include_in_result'] : '';

                                            echo '<td><hr /></td>';

                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'include_in_result',
                                                'labeltext'  => esc_html__('Archive page result', 'simpletags'),
                                                'aftertext'  => esc_html__('Show content from all post types on archive page',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);


                                                ?>

                                            </table>


                                            <table class="form-table taxopress-table taxonomy_slug"
                                                   style="display:none;">
                                                <?php


                                                if($taxonomy_edit){
                                                    
                                                    echo $ui->get_th_start();
                                                echo $ui->get_label('name',
                                                        esc_html__('Taxonomy Slug',
                                                            'simpletags')) . $ui->get_required_span();

                                                if ('edit' === $tab) {
                                                    echo '<p id="slugchanged" class="hidemessage">' . esc_html__('Slug has changed',
                                                            'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';
                                                }
                                                echo '<p id="slugexists" class="hidemessage">' . esc_html__('Slug already exists',
                                                        'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';
                                                        
                                                echo '<p id="st-tags-slug-error-input" class="hidemessage">' . esc_html__('Special character not allowed in slug.', 'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';

                                                echo $ui->get_th_end() . $ui->get_td_start();

                                                echo $ui->get_text_input([
                                                    'namearray'   => 'cpt_custom_tax',
                                                    'name'        => 'name',
                                                    'textvalue'   => isset($current['name']) ? esc_attr($current['name']) : '',
                                                    'maxlength'   => '32',
                                                    'helptext'    => 'Slugs can only contain alphanumeric, latin characters and underscores.',
                                                    'class'     => 'tax-slug-input',
                                                    'required'    => true,
                                                    'placeholder' => false,
                                                    'wrap'        => false,
                                                ]);

                                                if ('edit' === $tab) {
                                                    echo '<p>';
                                                    esc_html_e('DO NOT EDIT the taxonomy slug unless also planning to migrate terms. Changing the slug registers a new taxonomy entry.',
                                                        'simpletags');
                                                    echo '</p>';

                                                    echo '<div class="taxopress-spacer">';
                                                    echo $ui->get_check_input([
                                                        'checkvalue' => 'update_taxonomy',
                                                        'checked'    => 'false',
                                                        'name'       => 'update_taxonomy',
                                                        'namearray'  => 'update_taxonomy',
                                                        'labeltext'  => esc_html__('Migrate terms to newly renamed taxonomy?',
                                                            'simpletags'),
                                                        'helptext'   => '',
                                                        'default'    => false,
                                                        'wrap'       => false,
                                                    ]);
                                                    echo '</div>';
                                                }

                                            }

                                                ?>

                                            </table>


                                            <table class="form-table taxopress-table taxonomy_templates"
                                                   style="display:none;">
                                                   <?php if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                                <?php
                                                echo $ui->get_tr_start() . $ui->get_th_start();
                                                echo 'Template Hierarchy';
                                                echo $ui->get_th_end();

                                                $template_hierarchy_slug = isset($current['name']) ? esc_attr($current['name']) : '';
                                                $template_hierarchy = '
        <ul style="margin-top: 0;">
        <li style="list-style: decimal;">taxonomy-'.esc_html( $template_hierarchy_slug ).'-term_slug.php *</li>
        <li style="list-style: decimal;">taxonomy-'. esc_html( $template_hierarchy_slug ).'.php</li>
        <li style="list-style: decimal;">taxonomy.php</li>
        <li style="list-style: decimal;">archive.php</li>
        <li style="list-style: decimal;">index.php</li>
        </ul>
        <p style="font-weight:bolder;">'.esc_html__( '*Replace "term_slug" with the slug of the actual taxonomy term.', 'simpletags' ).'</p>';

        echo '<td>';
        echo $template_hierarchy;
        echo '</td>';
         }
                                               
                                                echo $ui->get_tr_end();


                                                ?>
                                            </table>


                                            <table class="form-table taxopress-table taxonomy_permalinks"
                                                   style="display:none;">
                                                <?php


                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr' => '0',
                                                        'text' => esc_attr__('False', 'simpletags'),
                                                    ],
                                                    [
                                                        'attr'    => '1',
                                                        'text'    => esc_attr__('True', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite']) : '';
                                            $select['selected'] = !empty($selected) ? $current['rewrite'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'rewrite',
                                                'labeltext'  => esc_html__('Rewrite', 'simpletags'),
                                                'aftertext'  => esc_html__('WordPress can use a custom permalink for this taxonomy. It does not have to match the slug.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                            echo $ui->get_text_input([
                                                'namearray' => 'cpt_custom_tax',
                                                'name'      => 'rewrite_slug',
                                                'textvalue' => isset($current['rewrite_slug']) ? esc_attr($current['rewrite_slug']) : '',
                                                'aftertext' => esc_attr__('(default: taxonomy name)', 'simpletags'),
                                                'labeltext' => esc_html__('Custom Rewrite Slug', 'simpletags'),
                                                'helptext'  => esc_html__('Custom taxonomy rewrite slug.',
                                                    'simpletags'),
                                            ]);

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr' => '0',
                                                        'text' => esc_attr__('False', 'simpletags'),
                                                    ],
                                                    [
                                                        'attr'    => '1',
                                                        'text'    => esc_attr__('True', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite_withfront']) : '';
                                            $select['selected'] = !empty($selected) ? $current['rewrite_withfront'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'rewrite_withfront',
                                                'labeltext'  => esc_html__('Rewrite With Front', 'simpletags'),
                                                'aftertext'  => esc_html__('Should the permastruct be prepended with the front base.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr'    => '0',
                                                        'text'    => esc_attr__('False', 'simpletags'),
                                                        'default' => 'false',
                                                    ],
                                                    [
                                                        'attr' => '1',
                                                        'text' => esc_attr__('True', 'simpletags'),
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite_hierarchical']) : '';
                                            $select['selected'] = !empty($selected) ? $current['rewrite_hierarchical'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'rewrite_hierarchical',
                                                'labeltext'  => esc_html__('Rewrite Hierarchical', 'simpletags'),
                                                'aftertext'  => esc_html__('Should the permastruct allow hierarchical urls.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                                ?>

                                            </table>


                                            <table class="form-table taxopress-table taxonomy_menus"
                                                   style="display:none;">
                                                <?php


                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr' => '0',
                                                            'text' => esc_attr__('False', 'simpletags'),
                                                        ],
                                                        [
                                                            'attr'    => '1',
                                                            'text'    => esc_attr__('True', 'simpletags'),
                                                            'default' => 'true',
                                                        ],
                                                    ],
                                                ];
                                                $selected           = isset($current) ? taxopress_disp_boolean($current['show_ui']) : '';
                                                $select['selected'] = !empty($selected) ? $current['show_ui'] : '';
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'cpt_custom_tax',
                                                    'name'       => 'show_ui',
                                                    'labeltext'  => esc_html__('Show user interface', 'simpletags'),
                                                    'aftertext'  => '',
                                                    'selections' => $select,
                                                ]);

                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr' => '0',
                                                            'text' => esc_attr__('False', 'simpletags'),
                                                        ],
                                                        [
                                                            'attr'    => '1',
                                                            'text'    => esc_attr__('True', 'simpletags'),
                                                            'default' => 'true',
                                                        ],
                                                    ],
                                                ];
                                                $selected           = isset($current) ? taxopress_disp_boolean($current['show_in_menu']) : '';
                                                $select['selected'] = !empty($selected) ? $current['show_in_menu'] : '';
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'cpt_custom_tax',
                                                    'name'       => 'show_in_menu',
                                                    'labeltext'  => esc_html__('Show in admin menus', 'simpletags'),
                                                    'aftertext'  => '',
                                                    'selections' => $select,
                                                ]);

                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr' => '0',
                                                            'text' => esc_attr__('False', 'simpletags'),
                                                        ],
                                                        [
                                                            'attr'    => '1',
                                                            'text'    => esc_attr__('True', 'simpletags'),
                                                            'default' => 'true',
                                                        ],
                                                    ],
                                                ];
                                                $selected           = (isset($current) && !empty($current['show_in_nav_menus'])) ? taxopress_disp_boolean($current['show_in_nav_menus']) : '';
                                                $select['selected'] = !empty($selected) ? $current['show_in_nav_menus'] : '';
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'cpt_custom_tax',
                                                    'name'       => 'show_in_nav_menus',
                                                    'labeltext'  => esc_html__('Show in frontend menus', 'simpletags'),
                                                    'aftertext'  => '',
                                                    'selections' => $select,
                                                ]);


                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr'    => '0',
                                                            'text'    => esc_attr__('False', 'simpletags'),
                                                            'default' => 'true',
                                                        ],
                                                        [
                                                            'attr' => '1',
                                                            'text' => esc_attr__('True', 'simpletags'),
                                                        ],
                                                    ],
                                                ];
                                                $selected           = isset($current) ? taxopress_disp_boolean($current['show_admin_column']) : '';
                                                $select['selected'] = !empty($selected) ? $current['show_admin_column'] : '';
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'cpt_custom_tax',
                                                    'name'       => 'show_admin_column',
                                                    'labeltext'  => esc_html__('Show admin column', 'simpletags'),
                                                    'aftertext'  => '',
                                                    'selections' => $select,
                                                ]);

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr'    => '0',
                                                        'text'    => esc_attr__('False', 'simpletags'),
                                                        'default' => 'false',
                                                    ],
                                                    [
                                                        'attr' => '1',
                                                        'text' => esc_attr__('True', 'simpletags'),
                                                    ],
                                                ],
                                            ];
                                            $selected           = (isset($current) && !empty($current['show_in_quick_edit'])) ? taxopress_disp_boolean($current['show_in_quick_edit']) : '';
                                            $select['selected'] = !empty($selected) ? $current['show_in_quick_edit'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'show_in_quick_edit',
                                                'labeltext'  => esc_html__('Show in "Quick Edit" and "Bulk Edit"',
                                                    'simpletags'),
                                                'aftertext'  => '',
                                                'selections' => $select,
                                            ]);

                                                ?>

                                            </table>


                                            <table class="form-table taxopress-table taxonomy_labels"
                                                   style="display:none;">

                                                <?php

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'menu_name',
                                                    'textvalue' => isset($current['labels']['menu_name']) ? esc_attr($current['labels']['menu_name']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('Menu Name', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom admin menu name for your taxonomy.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        'label'     => 'item', // Not localizing because it's isolated.
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'all_items',
                                                    'textvalue' => isset($current['labels']['all_items']) ? esc_attr($current['labels']['all_items']) : '',
                                                    'aftertext' => esc_attr__('(e.g. All Jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('All Items', 'simpletags'),
                                                    'helptext'  => esc_html__('Used as tab text when showing all terms for hierarchical taxonomy while editing post.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('All %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'edit_item',
                                                    'textvalue' => isset($current['labels']['edit_item']) ? esc_attr($current['labels']['edit_item']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Edit Job)', 'simpletags'),
                                                    'labeltext' => esc_html__('Edit Item', 'simpletags'),
                                                    'helptext'  => esc_html__('Used at the top of the term editor screen for an existing taxonomy term.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Edit %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'view_item',
                                                    'textvalue' => isset($current['labels']['view_item']) ? esc_attr($current['labels']['view_item']) : '',
                                                    'aftertext' => esc_attr__('(e.g. View Job)', 'simpletags'),
                                                    'labeltext' => esc_html__('View Item', 'simpletags'),
                                                    'helptext'  => esc_html__('Used in the admin bar when viewing editor screen for an existing taxonomy term.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('View %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'update_item',
                                                    'textvalue' => isset($current['labels']['update_item']) ? esc_attr($current['labels']['update_item']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Update Job Name)', 'simpletags'),
                                                    'labeltext' => esc_html__('Update Item Name', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Update %s name',
                                                            'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'add_new_item',
                                                    'textvalue' => isset($current['labels']['add_new_item']) ? esc_attr($current['labels']['add_new_item']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Add New Job)', 'simpletags'),
                                                    'labeltext' => esc_html__('Add New Item', 'simpletags'),
                                                    'helptext'  => esc_html__('Used at the top of the term editor screen and button text for a new taxonomy term.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Add new %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'new_item_name',
                                                    'textvalue' => isset($current['labels']['new_item_name']) ? esc_attr($current['labels']['new_item_name']) : '',
                                                    'aftertext' => esc_attr__('(e.g. New Job Name)', 'simpletags'),
                                                    'labeltext' => esc_html__('New Item Name', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('New %s name', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'parent_item',
                                                    'textvalue' => isset($current['labels']['parent_item']) ? esc_attr($current['labels']['parent_item']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Parent Job)', 'simpletags'),
                                                    'labeltext' => esc_html__('Parent Item', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Parent %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'parent_item_colon',
                                                    'textvalue' => isset($current['labels']['parent_item_colon']) ? esc_attr($current['labels']['parent_item_colon']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Parent Job:)', 'simpletags'),
                                                    'labeltext' => esc_html__('Parent Item Colon', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Parent %s:', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'singular',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'search_items',
                                                    'textvalue' => isset($current['labels']['search_items']) ? esc_attr($current['labels']['search_items']) : '',
                                                    'aftertext' => esc_attr__('(e.g. Search Jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('Search Items', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Search %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'popular_items',
                                                    'textvalue' => isset($current['labels']['popular_items']) ? esc_attr($current['labels']['popular_items']) : null,
                                                    'aftertext' => esc_attr__('(e.g. Popular Jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('Popular Items', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Popular %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'separate_items_with_commas',
                                                    'textvalue' => isset($current['labels']['separate_items_with_commas']) ? esc_attr($current['labels']['separate_items_with_commas']) : null,
                                                    'aftertext' => esc_attr__('(e.g. Separate Jobs with commas)',
                                                        'simpletags'),
                                                    'labeltext' => esc_html__('Separate Items with Commas',
                                                        'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Separate %s with commas',
                                                            'simpletags'), 'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'add_or_remove_items',
                                                    'textvalue' => isset($current['labels']['add_or_remove_items']) ? esc_attr($current['labels']['add_or_remove_items']) : null,
                                                    'aftertext' => esc_attr__('(e.g. Add or remove Jobs)',
                                                        'simpletags'),
                                                    'labeltext' => esc_html__('Add or Remove Items', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Add or remove %s',
                                                            'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'choose_from_most_used',
                                                    'textvalue' => isset($current['labels']['choose_from_most_used']) ? esc_attr($current['labels']['choose_from_most_used']) : null,
                                                    'aftertext' => esc_attr__('(e.g. Choose from the most used Jobs)',
                                                        'simpletags'),
                                                    'labeltext' => esc_html__('Choose From Most Used', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Choose from the most used %s',
                                                            'simpletags'), 'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'not_found',
                                                    'textvalue' => isset($current['labels']['not_found']) ? esc_attr($current['labels']['not_found']) : null,
                                                    'aftertext' => esc_attr__('(e.g. No Jobs found)', 'simpletags'),
                                                    'labeltext' => esc_html__('Not found', 'simpletags'),
                                                    'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('No %s found', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'no_terms',
                                                    'textvalue' => isset($current['labels']['no_terms']) ? esc_attr($current['labels']['no_terms']) : null,
                                                    'aftertext' => esc_html__('(e.g. No jobs)', 'simpletags'),
                                                    'labeltext' => esc_html__('No terms', 'simpletags'),
                                                    'helptext'  => esc_attr__('Used when indicating that there are no terms in the given taxonomy associated with an object.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('No %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'items_list_navigation',
                                                    'textvalue' => isset($current['labels']['items_list_navigation']) ? esc_attr($current['labels']['items_list_navigation']) : null,
                                                    'aftertext' => esc_html__('(e.g. Jobs list navigation)',
                                                        'simpletags'),
                                                    'labeltext' => esc_html__('Items List Navigation', 'simpletags'),
                                                    'helptext'  => esc_attr__('Screen reader text for the pagination heading on the term listing screen.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('%s list navigation',
                                                            'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'items_list',
                                                    'textvalue' => isset($current['labels']['items_list']) ? esc_attr($current['labels']['items_list']) : null,
                                                    'aftertext' => esc_html__('(e.g. Jobs list)', 'simpletags'),
                                                    'labeltext' => esc_html__('Items List', 'simpletags'),
                                                    'helptext'  => esc_attr__('Screen reader text for the items list heading on the term listing screen.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('%s list', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'not_found',
                                                    'textvalue' => isset($current['labels']['not_found']) ? esc_attr($current['labels']['not_found']) : null,
                                                    'aftertext' => esc_html__('(e.g. No jobs found)', 'simpletags'),
                                                    'labeltext' => esc_html__('Not Found', 'simpletags'),
                                                    'helptext'  => esc_attr__('The text displayed via clicking ‘Choose from the most used items’ in the taxonomy meta box when no items are available.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('No %s found', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_tax_labels',
                                                    'name'      => 'back_to_items',
                                                    'textvalue' => isset($current['labels']['back_to_items']) ? esc_attr($current['labels']['back_to_items']) : null,
                                                    'aftertext' => esc_html__('(e.g. &larr; Back to jobs',
                                                        'simpletags'),
                                                    'labeltext' => esc_html__('Back to Items', 'simpletags'),
                                                    'helptext'  => esc_attr__('The text displayed after a term has been updated for a link back to main index.',
                                                        'simpletags'),
                                                    'data'      => [
                                                        /* translators: Used for autofill */
                                                        'label'     => sprintf(esc_attr__('Back to %s', 'simpletags'),
                                                            'item'),
                                                        'plurality' => 'plural',
                                                    ],
                                                ]);
                                                ?>
                                            </table>

                                            <table class="form-table taxopress-table taxonomy_restapi"
                                                   style="display:none;">
                                                <?php

                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr' => '0',
                                                            'text' => esc_attr__('False', 'simpletags'),
                                                        ],
                                                        [
                                                            'attr'    => '1',
                                                            'text'    => esc_attr__('True', 'simpletags'),
                                                            'default' => 'true',
                                                        ],
                                                    ],
                                                ];
                                                $selected           = isset($current) ? taxopress_disp_boolean($current['show_in_rest']) : '';
                                                $select['selected'] = !empty($selected) ? $current['show_in_rest'] : '';
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'cpt_custom_tax',
                                                    'name'       => 'show_in_rest',
                                                    'labeltext'  => esc_html__('Show in REST API', 'simpletags'),
                                                    'aftertext'  => '',
                                                    'selections' => $select,
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_custom_tax',
                                                    'name'      => 'rest_base',
                                                    'labeltext' => esc_html__('REST API base slug', 'simpletags'),
                                                    'helptext'  => esc_attr__('Slug to use in REST API URLs.',
                                                        'simpletags'),
                                                    'textvalue' => isset($current['rest_base']) ? esc_attr($current['rest_base']) : '',
                                                ]);

                                                echo $ui->get_text_input([
                                                    'namearray' => 'cpt_custom_tax',
                                                    'name'      => 'rest_controller_class',
                                                    'labeltext' => esc_html__('REST API controller class',
                                                        'simpletags'),
                                                    'aftertext' => esc_attr__('Custom controller to use instead of WP_REST_Terms_Controller.',
                                                        'simpletags'),
                                                    'textvalue' => isset($current['rest_controller_class']) ? esc_attr($current['rest_controller_class']) : '',
                                                ]);


                                                ?>

                                        </div>


                                        <table class="form-table taxopress-table taxonomy_advanced"
                                               style="display:none;">
                                            <?php

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr' => '0',
                                                        'text' => esc_attr__('False', 'simpletags'),
                                                    ],
                                                    [
                                                        'attr'    => '1',
                                                        'text'    => esc_attr__('True', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['public']) : '';
                                            $select['selected'] = !empty($selected) ? $current['public'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'public',
                                                'labeltext'  => esc_html__('Public', 'simpletags'),
                                                'aftertext'  => esc_html__('Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr' => '0',
                                                        'text' => esc_attr__('False', 'simpletags'),
                                                    ],
                                                    [
                                                        'attr'    => '1',
                                                        'text'    => esc_attr__('True', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['publicly_queryable']) : '';
                                            $select['selected'] = !empty($selected) ? $current['publicly_queryable'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'publicly_queryable',
                                                'labeltext'  => esc_html__('Public Queryable', 'simpletags'),
                                                'aftertext'  => esc_html__('Whether or not the taxonomy should be publicly queryable.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                            $select             = [
                                                'options' => [
                                                    [
                                                        'attr' => '0',
                                                        'text' => esc_attr__('False', 'simpletags'),
                                                    ],
                                                    [
                                                        'attr'    => '1',
                                                        'text'    => esc_attr__('True', 'simpletags'),
                                                        'default' => 'true',
                                                    ],
                                                ],
                                            ];
                                            $selected           = isset($current) ? taxopress_disp_boolean($current['query_var']) : '';
                                            $select['selected'] = !empty($selected) ? $current['query_var'] : '';
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'cpt_custom_tax',
                                                'name'       => 'query_var',
                                                'labeltext'  => esc_html__('Query Var', 'simpletags'),
                                                'aftertext'  => esc_html__('Sets the query_var key for this taxonomy.',
                                                    'simpletags'),
                                                'selections' => $select,
                                            ]);

                                            echo $ui->get_text_input([
                                                'namearray' => 'cpt_custom_tax',
                                                'name'      => 'query_var_slug',
                                                'textvalue' => isset($current['query_var_slug']) ? esc_attr($current['query_var_slug']) : '',
                                                'aftertext' => esc_attr__('Query var needs to be true to use.',
                                                    'simpletags'),
                                                'labeltext' => esc_html__('Custom Query Var String', 'simpletags'),
                                                'helptext'  => esc_html__('Sets a custom query_var slug for this taxonomy.',
                                                    'simpletags'),
                                            ]);

                                            echo $ui->get_text_input([
                                                'namearray' => 'cpt_custom_tax',
                                                'name'      => 'meta_box_cb',
                                                'textvalue' => isset($current['meta_box_cb']) ? esc_attr($current['meta_box_cb']) : '',
                                                'labeltext' => esc_html__('Metabox callback', 'simpletags'),
                                                'helptext'  => esc_html__('Sets a callback function name for the meta box display. Hierarchical default: post_categories_meta_box, non-hierarchical default: post_tags_meta_box. To remove the metabox completely, use "false".',
                                                    'simpletags'),
                                            ]);

                                            echo $ui->get_text_input([
                                                'namearray' => 'cpt_custom_tax',
                                                'name'      => 'default_term',
                                                'textvalue' => isset($current['default_term']) ? esc_attr($current['default_term']) : '',
                                                'labeltext' => esc_html__('Default Term', 'simpletags'),
                                                'helptext'  => esc_html__('Set a default term for the taxonomy. Able to set a name, slug, and description. Only a name is required if setting a default, others are optional. Set values in the following order, separated by comma. Example: name, slug, description',
                                                    'simpletags'),
                                            ]);
                                            ?>
                                        </table>


                                            <table class="form-table taxopress-table taxonomy_delete"
                                                   style="display:none;">
                                                <?php
                                                echo $ui->get_tr_start() . $ui->get_th_start();

                                                ?>
<?php
                            if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>


                                <?php

                                $activate_action_link   = add_query_arg(
                                    [
                                        'page'               => 'st_taxonomies',
                                        'add'                => 'taxonomy',
                                        'action'             => 'edit',
                                        'action2'            => 'taxopress-reactivate-taxonomy',
                                        'taxonomy'           => esc_attr($request_tax),
                                        '_wpnonce'           => wp_create_nonce('taxonomy-action-request-nonce'),
                                        'taxopress_taxonomy' => $request_tax,
                                    ],
                                    taxopress_admin_url('admin.php')
                                );
                                $deactivate_action_link = add_query_arg(
                                    [
                                        'page'               => 'st_taxonomies',
                                        'add'                => 'taxonomy',
                                        'action'             => 'edit',
                                        'action2'            => 'taxopress-deactivate-taxonomy',
                                        'taxonomy'           => esc_attr($request_tax),
                                        '_wpnonce'           => wp_create_nonce('taxonomy-action-request-nonce'),
                                        'taxopress_taxonomy' => $request_tax,
                                    ],
                                    taxopress_admin_url('admin.php')
                                );

                                if (in_array($request_tax, taxopress_get_deactivated_taxonomy())) {
                                    ?>
                                    <span class="action-button reactivate"><a class="button-primary"
                                            href="<?php echo $activate_action_link; ?>"><?php echo __('Re-activate Taxonomy',
                                                'simpletags'); ?></a></span>
                                <?php } else { ?>
                                    <span class="action-button deactivate"><a class="button-primary"
                                            href="<?php echo $deactivate_action_link; ?>"><?php echo __('Deactivate Taxonomy',
                                                'simpletags'); ?></a></span>
                                <?php }
                                /**
                                 * Filters the text value to use on the button when deleting.
                                 *
                                 * @param string $value Text to use for the button.
                                 */
                                if (!$external_edit) {
                                    ?>
                                    <input type="submit" class="button-secondary taxopress-delete-bottom"
                                           name="cpt_delete"
                                           id="cpt_submit_delete"
                                           value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_delete',
                                               __('Delete Taxonomy', 'simpletags'))); ?>"/>
                                <?php }else{
                                     echo '<div class="taxopress-warning" style="color:red;">' . __('You can only delete taxonomies created with TaxoPress.',
                                    'simpletags') . '</div>';
                                }
                            }
                            ?>

                                                <?php
                                               
                                                echo $ui->get_th_end(). $ui->get_tr_end();


                                                ?>

                                            </table>


                                    </div>
                                    <div class="clear"></div>


                                </div>
                            </div>
                        </div>


                        <?php
                        /**
                         * Fires after the default fieldsets on the taxonomy screen.
                         *
                         * @param taxopress_admin_ui $ui Admin UI instance.
                         */
                        do_action('taxopress_taxonomy_after_fieldsets', $ui);
                        ?>

                    </div>
                </div>
            </form>
        </div><!-- End .wrap -->

        <div class="clear"></div>
        <?php
    }

}