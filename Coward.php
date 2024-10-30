<?php
/*
Plugin Name: Coward
Plugin URI: http://wordpress.org/extend/plugins/coward/
Description: Anonymizes new users' display names, nick's, and author urls
Author: Mike O'Malley
Version: 0.9.2
Author URI: 
*/

class Coward
{

	public function __construct()
	{
		/*
		if (get_option('coward_name') === null) {
			update_option('coward_name') = "Anonymous Coward %d";
		}
		 */

		$this->settings_name  = 'coward_options';
		$this->settings_page  = 'coward';

		$this->register_hooks();
	}

	public function register_hooks()
	{
		add_filter('the_author', array($this, 'author_name'));
		add_filter('generate_rewrite_rules', array($this, 'rewrite'));
		add_action('init', array($this, 'maybe_flush_rules'));
		add_filter('author_link', array($this, 'author_link'), 10, 2);
		add_filter('pre_user_login', array($this, 'check_new_coward'));
		add_filter('pre_user_display_name', array($this, 'set_displayname'));
		add_action('admin_notices', array($this, 'anonomize_notice'));
		add_action('admin_menu', array($this, 'settings'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	public function author_name($a)
	{
		global $authordata;
		if ($authordata->display_name == $authordata->user_login) {
			if (isset($authordata->ID)) $number = " #" . $authordata->ID;
			else $number = "";
			return "Anonymous Coward $number";
		} else return $a;
	}

	public function author_rewrite()
	{
		global $wp_rewrite;
		$wp_rewrite->rules = array('author/(\d+)$' => 'index.php?author=$matches[1]') + $wp_rewrite->rules;
	}

	public function maybe_flush_rules()
	{
		global $wp_rewrite;
		if ((int)get_option('coward_rewrite_version', 0) !== 1) {
			error_log("Flushing rewrite_rules");
			$wp_rewrite->flush_rules();
			update_option('coward_rewrite_version', 1);
		}
	}

	public function author_link($link, $id)
	{
		global $wp_rewrite;
		return str_replace('%author%', $id, $wp_rewrite->get_author_permastruct());	
	}

	public function check_new_coward($l)
	{
		global $wpdb, $new_coward;
		$new_coward = ((get_user_by('login', $l) === false) ? true : false);
		return $l;
	}

	public function set_displayname($n)
	{
		global $new_coward;
		if ($new_coward) return "Anonymous Coward";
		return $n;
	}


	public function anonomize_notice()
	{
		global $current_screen;
		if ($current_screen->parent_base == 'edit') 
			echo "<div class='error'>[WARNING] Remember to anonomize post data to stay out of trouble!</div>";
	}

	public function settings()
	{
		add_options_page('Coward Settings', 'Coward', 'manage_options', 'coward', array($this, 'settings_page'));
	}

	public function register_settings()
	{
		register_setting($this->settings_page, $this->settings_name);
		add_settings_section('coward_general', 'General', array($this, 'print_settings'), $this->settings_page);
		add_settings_field('coward_name_format', 'Coward Name Format', 
			array($this, 'name_format_setting'), $this->settings_page, 'coward_general');
	}

	public function print_settings()
	{
		echo "<p>Coward General Settings</p>";
		print_r($_POST);
	}

	public function name_format_setting()
	{
		$options = get_option($this->settings_name);
		echo "<input id='coward_name_format' name='{$this->settings_name}[name_format]' 
			size='40' type='text' value='{$options['name_format']}' />";
	}

	public function settings_page()
	{
?>
<div class="wrap">
	<h2>Coward Settings</h2>
	<form method="post">
		<?php settings_fields($this->settings_name); ?>
		<?php do_settings_sections($this->settings_page); ?>
		<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>"/>
	</form>
</div>
<?php
	}

}

if (!isset($Coward)) $Coward = new Coward();
else error_log("Coward could not be enabled");
