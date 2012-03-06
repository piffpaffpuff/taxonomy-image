<?php

/*
 * Plugin Name: Taxonomy Image
 * Plugin URI: 
 * Description: Image for any Taxonomy
 * Version: 1.0
 * Author: Iwan Negro
 * Author URI: http://www.iwannegro.ch
 *
 * Copyright (C) 2011 Iwan Negro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
 
// ------------------------------------------
// classes
// ------------------------------------------

/**
 * Gui class
 */
if (!class_exists('Taxonomy_Image')) 
{
class Taxonomy_Image
{	
	public $plugin_url;	
	public $taxonomy;
	public $metadata_name;
	public $options_name;
	public $enabled_taxonomies;

	/**
	 * Constructor
	 */
	public function Taxonomy_Image()
	{	
		$this->plugin_url = plugins_url( '', __FILE__ );
		$this->options_name = 'taxonomy_image_options';	
		$this->metadata_name = 'taxonomy_image_id';
		
		add_action( 'init', array( $this, 'init_hook' ) );
		add_action( 'admin_init', array( $this, 'init_admin_hook' ) );
	}
	
	/**
	 * Create settings at activation
	 */
	public function add_default_settings()
	{
		global $wpdb;
		
		// Check the collate
		$collate = '';
	    if($wpdb->supports_collation()) {
			if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	    }
	    
		// Install table, if it doesnt exist already
		$sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "termmeta" ." (
			`meta_id` bigint(20) unsigned NOT NULL auto_increment,
			`term_id` bigint(20) unsigned NOT NULL default '0',
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY (meta_id),
			KEY term_id (term_id),
			KEY meta_key (meta_key) ) $collate;";
			
	    $wpdb->query($sql);
	}
	
	/**
	 * Register any created table
	 */
	public function init_hook()
	{
		global $wpdb;
		
		// Register new table
		$wpdb->termmeta = $wpdb->prefix . 'termmeta';
		$this->enabled_taxonomies = get_option($this->options_name);
		
		// Load text domain
		load_plugin_textdomain('taxonomy-image', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		//
	/*
			$sql = "SELECT * FROM " . $wpdb->termmeta . " WHERE meta_key LIKE 'insofern_taxonomy_image_id' AND meta_value NOT LIKE ''";
		$results = $wpdb->get_results($sql);
		
		foreach($results as $result){
			update_metadata('term', $result->term_id, 'taxonomy_image_id', $result->meta_value);
		}
*/
		
	}
	
	/**
	 * Init all admin hooks
	 */
	public function init_admin_hook()
	{
		// Get the taxonomy
		if( isset( $_GET['taxonomy'] ) ) 
		{
			$this->taxonomy = $_GET['taxonomy'];
		} 
		else if( isset( $_POST['taxonomy'] ) ) 
		{
			$this->taxonomy = $_POST['taxonomy'];
		}
		else 
		{
			$this->taxonomy = null;
		}	
		
		// Modify media uploader
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_media_button' ), 30 , 2 );
		add_action( 'admin_print_scripts-media-upload-popup', array( $this, 'add_media_scripts' ) );			
			
		// Get the option to check if this is an enabled taxonomy		
		if( isset( $this->taxonomy ) && isset( $this->enabled_taxonomies ) &&  in_array( $this->taxonomy, $this->enabled_taxonomies ) ) 
		{
			add_action( $this->taxonomy . '_add_form_fields', array( $this, 'create_content' ) );
			add_action( $this->taxonomy . '_edit_form_fields', array( $this, 'create_content' ) );
			
			add_action( 'edited_' . $this->taxonomy, array( $this, 'save_taxonomy' ) );
			add_action( 'created_' . $this->taxonomy, array( $this, 'save_taxonomy' ) );
		
			add_filter( 'manage_edit-' . $this->taxonomy . '_columns', array( $this,'add_columns' ) );
			add_filter( 'manage_' . $this->taxonomy . '_custom_column', array( $this,'create_column_content' ), 10, 3 );	
			
			add_action( 'admin_print_styles', array( $this, 'add_styles' ) );
			add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		}		
	}
	
	/**
	 * Add the styles
	 */
	public function add_styles()
	{
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'insofern-taxonomy-image', $this->plugin_url . '/css/style.css' );
	}
	
	/**
	 * Add the scripts
	 */
	public function add_scripts()
	{
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'insofern-taxonomy-image', $this->plugin_url . '/js/script.js', array( 'jquery', 'media-upload', 'thickbox' ) );
	}
	
	/**
	 * Add the media uploader scripts
	 */
	public function add_media_scripts()
	{
		wp_enqueue_script( 'insofern-taxonomy-image-media', $this->plugin_url . '/js/media-uploader.js', array( 'jquery' ) );
	}
	
	/**
	 * Add columns
	 */
	public function add_columns( $columns ) 
	{
		unset( $columns['description'] );
		$ordered_columns = array();
		foreach($columns as $column => $title) 
		{
		    if ($column == 'slug' ) 
		    {
				$ordered_columns['taxonomy_image_column'] =  __( 'Image', 'taxonomy-image' );
				$ordered_columns['taxonomy_description_column'] =  __( 'Description' );
			}
			
			$ordered_columns[$column] = $title;
		}
               
        return $ordered_columns;
    }
    
    /**
	 * Create column content
	 */
    public function create_column_content( $string, $column_name, $term )
    {
    	switch( $column_name )
    	{
    		case 'taxonomy_image_column':
    			$meta = get_metadata( 'term', $term, $this->metadata_name, true );
				$src = wp_get_attachment_image_src( $meta, array(50, 50) );
				
				if( !empty( $src ) )
				{
					?>
					<img src="<?php echo $src[0]; ?>" width="<?php echo $src[1]; ?>" height="<?php echo $src[2]; ?>" />
					<?php 
				}
    			break;
    		case 'taxonomy_description_column':
    			global $taxonomy;
				$string = term_description( $term, $taxonomy );
				$string = strip_tags( $string );
				$string = trim( $string );
				$string = html_entity_decode( $string, ENT_QUOTES, 'UTF-8' );
				$string = rtrim( $string, '-' );
				
				$max_length = 50;
				$length = strlen( $string );
				
				if ( $length > $max_length ) {
					$string = substr_replace( $string, '...', $max_length );
				}
				
				echo $string;
    			break;
    		default:
    			break;
    	}
    }

	/**
	 * Add content to the taxonomy
	 */
	public function create_content( $tag )
	{
		$meta = get_metadata( 'term', $tag->term_id, $this->metadata_name, true );
		$src = wp_get_attachment_image_src( $meta, 'thumbnail' );

		// switch the output if not listing the categories
		if( isset($_GET['action']) && $_GET['action'] == 'edit' ) : ?>
		<tr class="form-field hide-if-no-js">
			<th scope="row" valign="top">
				<label for="taxonomy_image"><?php _e( 'Image', 'taxonomy-image' ); ?></label>
			</th>
			<td>
				<input name="<?php echo $this->metadata_name; ?>" id="taxonomy_image_id" type="hidden" value="<?php echo $meta; ?>" />
				<span id="taxonomy_image_placeholder"><?php if( !empty( $src ) ) : ?><img src="<?php echo $src[0]; ?>" width="<?php echo $src[1]; ?>" height="<?php echo $src[2]; ?>" /><?php endif; ?></span>
				<a href="#" id="taxonomy_image_remove_button" <?php if( !empty( $src ) ) : ?>style="display: inherit;"<?php endif; ?>><?php _e( 'Remove image', 'taxonomy-image' ); ?></a>
				<a href="media-upload.php?type=image&amp;TB_iframe=true" <?php if( !empty( $src ) ) : ?>style="display: none;"<?php endif; ?> id="taxonomy_image_add_button" class="thickbox"><?php _e( 'Set image', 'taxonomy-image' ); ?></a>
				<p class="description"><?php _e( 'The image is not prominent by default, however some themes may show it.', 'taxonomy-image' ); ?></p>
			</td>
		</tr>
		<?php else : ?>
		<div class="form-field hide-if-no-js">
			<label for="taxonomy_image"><?php _e( 'Image', 'taxonomy-image' ); ?></label>
			<input name="<?php echo $this->metadata_name; ?>" id="taxonomy_image_id" type="hidden" value="<?php echo $meta; ?>" />
			<span id="taxonomy_image_placeholder"><?php if( !empty( $src ) ) : ?><img src="<?php echo $src[0]; ?>" width="<?php echo $src[1]; ?>" height="<?php echo $src[2]; ?>" /><?php endif; ?></span>
			<a href="#" id="taxonomy_image_remove_button" <?php if( !empty( $src ) ) : ?>style="display: inherit;"<?php endif; ?>><?php _e( 'Remove image', 'taxonomy-image' ); ?></a>
			<a href="media-upload.php?type=image&amp;TB_iframe=true" <?php if( !empty( $src ) ) : ?>style="display: none;"<?php endif; ?> id="taxonomy_image_add_button" class="thickbox"><?php _e( 'Set image', 'taxonomy-image' ); ?></a>
			<p class="description"><?php _e( 'The image is not prominent by default, however some themes may show it.', 'taxonomy-image' ); ?></p>
		</div>		
		<?php endif;
	}
 	
 	/**
	 * Save the content
	 */
	public function save_taxonomy( $term_id )
	{   
		if (!$term_id) 
		{
			return;
		}

		if ( isset( $_POST[$this->metadata_name] ) && $_POST[$this->metadata_name] != '' )
		{
			update_metadata( 'term', $term_id, $this->metadata_name, $_POST[$this->metadata_name] );
		}
		else
		{
			delete_metadata( 'term', $term_id, $this->metadata_name );
		}		
	}
	
	/**
	 * Add an extra button to the media 
	 * uploader to set the catgory image
	 */
	public function add_media_button( $fields, $post ) 
	{	
		if ( isset( $_GET['post_id'] ) )
		{
			$calling_post_id = absint( $_GET['post_id'] );
		}
		elseif ( isset( $_POST ) && count( $_POST ) ) 
		{
			$calling_post_id = $post->post_parent;
		}
		
		// only add the category button to the
		// category managment media.php
		if( empty( $calling_post_id ) )
		{
			if ( isset( $fields['image-size'] ) && isset( $post->ID ) ) 
			{
				if( substr($post->post_mime_type, 0, 5) == 'image' && !isset( $_GET['attachment_id'] ) ) 
				{
					$attachment_id = $post->ID;
					$src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
					$fields['buttons']['tr'] = '<tr class="taxonomy_image_row"><td></td><td class="savesend"><a href="#" class="button" id="taxonomy_image_set_button_' . $attachment_id . '" onclick="setTaxonomyImage(\'' . $attachment_id . '\', \'' . $src[0] . '\');return false;">' . __( 'Use as image', 'taxonomy-image' ) . '</a></td></tr>';
				}
			}
		}
				
		return $fields;
	}
}
}


/**
 * Settings class
 */
if (!class_exists('Taxonomy_Image_Settings')) {
class Taxonomy_Image_Settings
{	
	private $options_name;
	private $form_data_name;

	/**
	 * Constructor
	 */
	public function Taxonomy_Image_Settings()
	{			
		$this->options_name = 'taxonomy_image_options';
		$this->form_data_name = 'taxonomy_image';
		
		add_action('admin_init', array($this, 'init_admin_hook'));
		add_action('admin_menu', array($this, 'add_page'));
	}
	
	/**
	 * Add default settings
	 */
	public function add_default_settings() 
	{
		$args = array(
			'public' => true,
			'show_ui' => true
		);
		
		$taxonomies = get_taxonomies($args);
		
		// fill in the default options
		$option = array();
		
		foreach($taxonomies as $key => $value)
		{
			$option[$value] = $value;
		}

		add_option($this->options_name, $option);
	}
	
	/**
	 * Remove default settings
	 */
	public function remove_default_settings() 
	{
		delete_option($this->options_name);
	}

	/**
	 * Register our settings. Add the settings section, and settings fields
	 */
	public function init_admin_hook()
	{
		register_setting('taxonomy_image_options_group', $this->options_name);
		
		add_settings_section('show_image_selection_section', '', array($this, 'create_show_image_selection_section'), __FILE__);
		add_settings_field('show_image_selection_checkboxes', __('Show image selection', 'taxonomy-image'), array($this, 'create_show_image_selection_checkboxes'), __FILE__, 'show_image_selection_section');
	}
	
	/**
	 * Add sub page to the Settings Menu
	 */
	public function add_page() 
	{
		add_options_page(__('Taxonomy Image', 'taxonomy-image'), __('Taxonomy Image', 'taxonomy-image'), 'administrator', __FILE__, array($this, 'create_page_content'));
	}
	
	/**
	 * Add the page structure to the sub page
	 */
	public function create_page_content() 
	{
		// Check that the user has the required capability 
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$hidden_submit = 'submit_hidden';
		
		// See if the user has posted us some information
		if( isset($_POST[$hidden_submit]) && $_POST[$hidden_submit] == 'submit' )
		{
			// Save the posted value in the database
			update_option( $this->options_name, $_POST[$this->form_data_name] );
			
			// Put an settings updated message on the screen
			?><div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div><?php
		}
		
		// Now display the settings editing screen
		?><div class="wrap">
		<?php screen_icon('options-general'); ?>
			<h2><?php _e('Taxonomy Image Settings', 'taxonomy-image'); ?></h2>
			<form action="" method="post">
				<input type="hidden" name="<?php echo $hidden_submit; ?>" value="submit">
				<?php settings_fields($this->options_name); ?>
				<?php do_settings_sections(__FILE__); ?>
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</p>
			</form>
		</div><?php
	}
	
	/**
	 * Create settings group
	 */
	public function create_show_image_selection_section() 
	{
	}

	/**
	 * Create settings group content
	 */
	public function create_show_image_selection_checkboxes() 
	{
		$options = get_option($this->options_name);

		$args = array(
			'public' => true,
			'show_ui' => true
		);
		
		$taxonomies = get_taxonomies($args);
		
		foreach($taxonomies as $key => $value)
		{			
			$taxonomy_object = get_taxonomy($value);
			$post_type_object = get_post_type_object($taxonomy_object->object_type[0]);
			
			?>
			<label><input name="<?php echo $this->form_data_name; ?>[<?php echo $value; ?>]" value="<?php echo $value; ?>" type="checkbox" <?php if(isset($options) && isset($options[$value])) { ?> checked="checked"<?php } ?> /> <?php echo $post_type_object->labels->name . ': ' . $taxonomy_object->labels->name; ?></label><br />
			<?php
		}
	}	
}
}


// ------------------------------------------
// Initialize
// ------------------------------------------


// initialize objects
$Taxonomy_Image = new Taxonomy_Image();
$Taxonomy_Image_Settings = new Taxonomy_Image_Settings();

// set the activation hooks
register_activation_hook(__FILE__, array( $Taxonomy_Image, 'add_default_settings'));
register_activation_hook(__FILE__, array( $Taxonomy_Image_Settings, 'add_default_settings'));
register_deactivation_hook(__FILE__, array( $Taxonomy_Image_Settings, 'remove_default_settings'));


// ------------------------------------------
// Api
// ------------------------------------------


/**
 * Get the taxonomy image id
 */
if ( !function_exists( 'get_taxonomy_image_id' ) ) 
{
function get_taxonomy_image_id() 
{	
	global $Taxonomy_Image;
	
	if ( is_tax() ) 
	{
		$term = get_queried_object();
		$meta = get_metadata( 'term', $term->term_id, $Taxonomy_Image->metadata_name, true );
		return $meta;
	}
	
	return;
}
}


/**
 * Get the taxonomy image html code
 */
if ( !function_exists( 'get_taxonomy_image' ) ) 
{
function get_taxonomy_image( $size = 'thumbnail' ) 
{	
	$id = get_taxonomy_image_id();
	
	if(!empty($id)) {
		$src = wp_get_attachment_image_src( $id, $size );

		return '<img src="' . $src[0] . '" width="' . $src[1] . '" height="' . $src[2] . '" class="taxonomy-image taxonomy-image-' . $size . '" />';
	} 
	
	return;
}
}

?>
