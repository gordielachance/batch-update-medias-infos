<?php

/*
 * Plugin Name: Batch Update Medias Infos
 * Plugin URI: https://wordpress.org/plugins/batch-update-medias-infos
 * Description: Allows you to update the title / description / filename of a several medias at once
 * Author: G.Breant
 * Version: 1.0.1
 * Author URI: https://profiles.wordpress.org/grosbouff/
 * License: GPL2+
 * Text Domain: bumi
 * Domain Path: /languages/
 */

class BatchUpdateMediasInfos{
    
    public $name = 'Batch Update Medias Infos';
    public $author = 'G.Breant';

    /** Version ***************************************************************/

    /**
    * @public string plugin version
    */
    public $version = '1.0.1';

    /**
    * @public string plugin DB version
    */
    public $db_version = '100';

    /** Paths *****************************************************************/

    public $file = '';

    /**
    * @public string Basename of the plugin directory
    */
    public $basename = '';

    /**
    * @public string Absolute path to the plugin directory
    */
    public $plugin_dir = '';


    /**
    * @var The one true Instance
    */
    private static $instance;
    
    static $meta_key_db_version = 'bumi-db';
    static $meta_key_options = 'bumi-options';
    
   /**
     * Main Instance
     *
     * Insures that only one instance of the plugin exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @staticvar array $instance
     * @uses ::setup_globals() Setup the globals needed
     * @uses ::includes() Include the required files
     * @uses ::setup_actions() Setup the hooks and actions
     * @return The instance
     */
    public static function instance() {
            if ( ! isset( self::$instance ) ) {
                    self::$instance = new BatchUpdateMediasInfos;
                    self::$instance->includes();
                    self::$instance->setup_globals();
                    self::$instance->setup_actions();
            }
            return self::$instance;
    }

    /**
     * A dummy constructor to prevent the plugin from being loaded more than once.
     */
    private function __construct() { /* Do nothing here */ }
    
    function includes(){
        //require( $this->plugin_dir . 'ari-settings.php');
    }

    function setup_globals(){
        global $wpdb;

        /** Paths *************************************************************/
        $this->file       = __FILE__;
        $this->basename   = plugin_basename( $this->file );
        $this->prefix = 'ari';
        $this->plugin_dir = plugin_dir_path( $this->file );
        $this->plugin_url = plugin_dir_url ( $this->file );

    }
    
    function setup_actions(){

        //localization
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        //upgrade
        add_action( 'init' , array($this, 'upgrade'));//install and upgrade

        //admin page
        add_action('admin_menu',  array( $this, 'enqueue_scripts_styles' ) );
        
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );


    }

    static function get_default_options(){
        $default = array(
            'media_ids'         =>  array(),
            'media_title'       =>  null,
            'force_title'       => false,
            'media_description' =>  null,
            'force_description' => false,
            'increment_title'   =>  true,
            'starting_number'   =>  1,
            'rename_files'      =>  true
        );
        return $default;
    }
    
    static function get_options(){
        
        $defaults = self::get_default_options();
        $db = get_option(self::$meta_key_options);
        $options = wp_parse_args($db, $defaults);

        return apply_filters('bumi_get_options',$options);
        
    }
    
    static function get_option($name){

        $options = self::get_options();
        if (isset($options[$name])){
            return $options[$name];
        }
        return false;
    }
    
    function upgrade(){
        global $wpdb;

        $current_version = get_option(self::$meta_key_db_version);
        if ( $current_version == $this->db_version ) return;

        //install
        if(!$current_version){
            //handle SQL
            //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            //dbDelta($sql);
            //add_option($option_name,$this->get_default_settings()); // add settings
        }

        //upgrade DB version
        update_option(self::$meta_key_db_version, $this->db_version );//upgrade DB version
    }
    
    public function load_plugin_textdomain(){
        load_plugin_textdomain($this->basename, FALSE, $this->plugin_dir.'/languages/');
    }
    
    public function enqueue_scripts_styles($hook){
        if ($hook!='settings_page_ari-admin') return;
        //wp_enqueue_style( 'bumi-admin', $this->plugin_url .'_inc/css/bumi-admin.css', array(), $this->version );
    }

    
    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_media_page(
                __('Batch Update Medias','bumi'),
                __('Batch Update Medias','bumi'),
                'manage_options',
                'bumi-options',
                array( $this, 'options_page' )
        );
    }
    
    /**
     * Options page callback
     */
    public function options_page(){
        // Set class property
        
        ?>
        <div class="wrap">
            <h2><?php _e('Batch Update Medias','bumi');?></h2>  
            
            
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'bumi_option_group' );   
                do_settings_sections( 'bumi-settings-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register and add settings
     */
    public function page_init(){        
        register_setting(
            'bumi_option_group', // Option group
            self::$meta_key_options, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        
        add_settings_section(
            'settings_medias', // ID
            __('Medias','bumi'), // Title
            array( $this, 'section_medias_desc' ), // Callback
            'bumi-settings-admin' // Page
        );
        
        add_settings_field(
            'media_ids', 
            __('Media IDs','bumi'), 
            array( $this, 'media_ids_callback' ), 
            'bumi-settings-admin', 
            'settings_medias'
        );
        
        add_settings_field(
            'media_title', 
            __('Medias Title','bumi'), 
            array( $this, 'media_title_callback' ), 
            'bumi-settings-admin', 
            'settings_medias'
        );
        
        add_settings_field(
            'media_description', 
            __('Medias Description','bumi'), 
            array( $this, 'media_description_callback' ), 
            'bumi-settings-admin', 
            'settings_medias'
        );

        add_settings_section(
            'settings_options', // ID
            __('Options','bumi'), // Title
            array( $this, 'section_options_desc' ), // Callback
            'bumi-settings-admin' // Page
        );  
        
        add_settings_field(
            'increment_title', 
            __('Increment Title','bumi'), 
            array( $this, 'increment_title_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );
        
        add_settings_field(
            'starting_number', 
            __('Starting Number','bumi'), 
            array( $this, 'starting_number_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );
        
        add_settings_field(
            'force_title', 
            __('Force Title','bumi'), 
            array( $this, 'force_title_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );
        
        add_settings_field(
            'force_description', 
            __('Force Description','bumi'), 
            array( $this, 'force_description_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );
        
        add_settings_field(
            'rename_files', 
            __('Rename Files','bumi'), 
            array( $this, 'rename_files_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );

        add_settings_field(
            'reset_options', 
            __('Reset Options','bumi'), 
            array( $this, 'reset_options_callback' ), 
            'bumi-settings-admin', 
            'settings_options'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){

        $new_input = array();
        $default_options = self::get_default_options();
        //get options

        if( isset( $input['reset_options'] ) ){
            
            $new_input = self::get_default_options();
            
        }else{ //sanitize values

            if( isset( $input['media_ids'] ) ){
                $new_input['media_ids'] = self::parse_media_ids($input['media_ids']);
            }
            
            if( isset( $input['media_title'] ) ){
                $new_input['media_title'] = $input['media_title'];
            }
            
            if( isset( $input['media_description'] ) ){
                $new_input['media_description'] = $input['media_description'];
            }
            
            $new_input['increment_title'] = (isset( $input['increment_title']));

            
            if( isset( $input['starting_number'] ) ){

                $num = intval($input['starting_number']);
                if ($num>1) $new_input['starting_number'] = $num;
 
            }
            
            $new_input['force_title'] = (isset($input['force_title']));
            $new_input['force_description'] = (isset($input['force_description']));
            $new_input['rename_files'] = (isset($input['rename_files']));
            
            $new_input = wp_parse_args($new_input, $default_options);
            
            $new_input['media_ids'] = self::process_medias($new_input); //returns the array of medias having not been processed
            
            if (empty($new_input['media_ids'])){ //success !
                unset($new_input['media_title'],$new_input['media_description']);
            }

        }
        
        return $new_input;
       
    }
    
    function parse_media_ids($string_or_array){
        
        $arr = array();
        
        if (is_array($string_or_array)){
            $arr = $string_or_array;
        }else{
            $arr = explode(',',$string_or_array);
        }

        //check for ranges
        foreach ($arr as $num){
            $explode = explode('-',$num);
            
            if (isset($explode[1]) && is_numeric($explode[0])  && is_numeric($explode[1]) ){ //range

                $range = range(intval($explode[0]),intval($explode[1]));
                $arr = array_merge($range,$arr);

            }

        }

        //remove non integers
        foreach ($arr as $key=>$num){
            if (!is_numeric($num)) unset($arr[$key]);
            $arr[$key] = intval($num);
        }
        
        //unique
        $arr = array_unique($arr);

        //order
        sort($arr,SORT_NUMERIC);

        return $arr;
    }
    
    /*
     * Check the array of IDs and returns a string from it, taking ranges into account.
     */
    
    function get_media_ids_string($media_ids){
        
        //$media_ids = '117,11,1,3,5,6,7,8,9,10,12,13,15,16,17,18,13,100,101,102,115'; //1,3,5-13,15-18,100-102,115,117
        
        $media_ids = self::parse_media_ids($media_ids);
        
        $ranges = array();
        $current_range = null;
        
        foreach ((array)$media_ids as $key=>$id){

            if ($key==0) continue;

            $last_id = $media_ids[$key-1];

            if ($id == $last_id+1){

                if (empty($current_range)){//no range yet, start it

                    $current_range = array($last_id,$id);

                }else{ //update range OUT

                    $current_range[1] = $id;
                }

            }else{

                if (!empty($current_range)){ //stop range

                    //populate first and last range key as an array, that we add to $ranges,
                    //then unset $current_range.
                    $current_range_key_in = array_search($current_range[0], $media_ids);
                    $current_range_key_out = array_search($current_range[1], $media_ids);
                    $ranges[] = array($current_range_key_in,$current_range_key_out);
                    unset($current_range);

                }

            }
        }
        
        foreach((array)$ranges as $range){
            
            //keys to remove from $media_ids for this range
            $remove_keys = range($range[0],$range[1]);
            
            $first_range_key = reset($range);
            $last_range_key = end($range);
            
            $range_values = array($media_ids[$first_range_key],$media_ids[$last_range_key]);
            $range_str = implode('-',$range_values);
            
            foreach((array)$media_ids as $key=>$id){
                
                if (in_array($key,$remove_keys)){
                    
                    if ($key != $last_range_key) {
                        
                        unset($media_ids[$key]); //remove each key from media_ids
                        
                    }else{
                        
                        $media_ids[$key] = $range_str;
                        
                    }
                    
                }
            }
        }
        
        
        return implode(',',$media_ids);
        
    }
    
    public function section_medias_desc(){
    }
    
    function media_ids_callback(){ 
        $option = self::get_option('media_ids');
        $option_string = self::get_media_ids_string($option);
        
        $desc = __('Enter the IDs of the medias you want to update,separated by a comma.  Ranges are accepted.','bumi').'<br/>';
        $desc .= sprintf(__('eg. %1$s.','bumi'),'<code>541, 542, 543-562, 564</code>');
     
        printf(
            '<input type="text" id="bumi-media-ids" name="%1$s[media_ids]" size="60" value="%2$s"/><br/>%3$s',
            self::$meta_key_options,
            $option_string,
            $desc
        );
    }
    
    function media_title_callback(){
        $option = self::get_option('media_title');
        $desc = __('Title to give to those medias','bumi');
     
        printf(
            '<input type="text" id="bumi-media-title" name="%1$s[media_title]" size="60" value="%2$s"/><br/>%3$s',
            self::$meta_key_options,
            $option,
            $desc
        );
    }
    
    function media_description_callback(){
        $option = self::get_option('media_description');
        $desc = __('Description to give to those medias','bumi');
        
        $quicktags_settings = array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' );
        $editor_args = array(
                'textarea_name' => self::$meta_key_options.'[media_description]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'tinymce' => false,
                'quicktags' => $quicktags_settings,
        );
     
        wp_editor( $option, 'bumi-media-description', $editor_args );
        echo'<br/>'.$desc;
    }
    
    public function section_options_desc(){
    }
    
    function increment_title_callback(){
        $option = self::get_option('increment_title');

        $checked = checked( (bool)$option, true, false );
        $desc = __('Add a numeral suffix (eg.<code>#3</code>) to the media title (medias will be processed in ascending IDs order)','bumi');
                
        printf(
            '<input type="checkbox" name="%1$s[increment_title]" value="on" %2$s/> %3$s',
            self::$meta_key_options,
            $checked,
            $desc
        );
    }
    
    function starting_number_callback(){
        $option = self::get_option('starting_number');

        $desc = __('Number the media title prefix should start at, if any','bumi');
     
        printf(
            '<input type="number" name="%1$s[starting_number]" value="%2$s" min="1" class="small-text"/> %3$s',
            self::$meta_key_options,
            $option,
            $desc
        );
    }
    
    function force_title_callback(){
        $option = self::get_option('force_title');

        $checked = checked( (bool)$option, true, false );
        $desc = __('Replace title even if it is already set','bumi');
                
        printf(
            '<input type="checkbox" name="%1$s[force_title]" value="on" %2$s/> %3$s',
            self::$meta_key_options,
            $checked,
            $desc
        );
    }
    
    function force_description_callback(){
        $option = self::get_option('force_description');

        $checked = checked( (bool)$option, true, false );
        $desc = __('Replace description even if it is already set','bumi');
                
        printf(
            '<input type="checkbox" name="%1$s[force_description]" value="on" %2$s/> %3$s',
            self::$meta_key_options,
            $checked,
            $desc
        );
    }
    
    function rename_files_callback(){
        $option = self::get_option('rename_files');
        $enabled = function_exists('mfrh_rename_media');
        $disabled = disabled($enabled,false,false);
        $checked = checked( (bool)$option, true, false );
        
        $desc = __('Rename media filename based on their new titles','bumi');
        
        if (!$enabled){
            $desc .= '<br/><small><strong>'.sprintf(__('The plugin %1$s is needed to enable this feature.','bumi'),'<a href="https://wordpress.org/plugins/media-file-renamer/" target="_blank">Media File Renamer</a>').'</strong></small>';
        }
                
        printf(
            '<input type="checkbox" name="%1$s[rename_files]" value="on" %2$s,%3$s/> %4$s',
            self::$meta_key_options,
            $checked,
            $disabled,
            $desc
        );
    }

    
    public function reset_options_callback(){
        printf(
            '<input type="checkbox" name="%1$s[reset_options]" value="on"/> %2$s',
            self::$meta_key_options,
            __("Reset options to their default values.","bumi")
        );
    }

    function process_medias($options){ ////http://textmechanic.com/Generate-List-of-Numbers.html

        $default_options = self::get_default_options();
        $options = wp_parse_args($options,$default_options);
        
        $media_ids = $options['media_ids'];
        
        if (!empty($media_ids)){
        
            //print_r($options);die("toto");

            //http://textmechanic.com/Generate-List-of-Numbers.html

            $args = array(
                'post_type'     => 'attachment',
                'post_status' => 'inherit',
                'showposts'   => -1,
                'post__in'       => $options['media_ids'],
                'order'         => 'ASC'
            );

            $query = new WP_Query($args);

            $i = $options['starting_number'] - 1;
            $count = count($media_ids);
            $digits = preg_match_all( "/[0-9]/", $count );

            foreach ($query->posts as $post){

                $i++;
                $index = sprintf('%0'.$digits.'d', $i);
                $media_ids_key = array_search($post->ID, $media_ids);

                if (self::process_single_media($post,$index,$options)){
                    unset($media_ids[$media_ids_key]);
                }

            }
            
        }
        
        return $media_ids; //return the IDs that have not been processed
    }
    
    function process_single_media($post,$index,$options){
 
        $original_post = clone $post;
        
        $default_options = self::get_default_options();
        $options = wp_parse_args($options,$default_options);

        //title
        if ( ($options['media_title']) && ( (!$post->post_title) || ($options['force_title']) ) ) {

            if ($options['increment_title']){
                
                $post->post_title = sprintf('%1$s #%2$s',$options['media_title'],$index);
                
            }else{
                
                $post->post_title = $options['media_title'];
                
            }

        }

        //description
        if ( ($options['media_description']) && ( (!$post->post_content) || ($options['force_description']) ) ) {

            $post->post_content = $options['media_description'];

        }

        //update
        if ( $post == $original_post ) return $post->ID;
        if ( !wp_update_post($post) ) return false;
        
        if ( $options['rename_files'] && function_exists('mfrh_rename_media') && ($post->post_title != $original_post->post_title) ){ //file names
           $attachment = get_post( $post->ID, ARRAY_A );
           $attachment = mfrh_rename_media( $attachment, $attachment, true );
        }
        
        return $post->ID;

    }
}

function bumi() {
	return BatchUpdateMediasInfos::instance();
}


bumi();


//self::process($post_id,80,129,"Mickey's Christmas Carol");
//self::process($post_id,266,313,'"Dragons" storyboard strip','Cut Scenes from "How to Train Your Dragon" Storyboards by Toby Shelton');
//self::process($post_id,314,338,'"KFP storyboard strip','Cut Scenes from "KFP: Secrets of the Masters" Storyboards by Toby Shelton');
//self::process($post_id,522,636,'"Tangled" storyboard strip');
//self::process($post_id,667,853,'"Megamind" storyboard strip',"Cut Scene from 'Megamind' Storyboards by Toby Shelton");
//self::process($post_id,854,937,'"Tangled" storyboard strip','Cut Scenes from "Tangled" Storyboards by Toby Shelton');
//self::process($post_id,938,966,'"The Worse Day Ever" storyboard strip','Storyboard art by Michael Lester');
//self::process($post_id,967,978,'"Netflix" storyboard strip','Storyboard art by Michael Lester');
//self::process($post_id,993,1007,'"Storyboard strip by Sandro Cleuzo','2D Animated Short Film Storyboards by Sandro Cleuzo');
//self::process($post_id,1008,1036,'"Visual Vocabulary','"Framed Ink: Drawing and Composition for Visual Storytellers" by Marcos Mateu-Mestre');
//self::process($post_id,1038,1087,'"Raiders of the Lost Ark" still');
//self::process($post_id,1090,1107,'"The Dark Knight" film still');
//self::process($post_id,1113,1136,'"Shining" film still');
//self::process($post_id,1139,1161,'"Let The Right One In" film still');

?>