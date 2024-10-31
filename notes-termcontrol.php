<?php
/**
 * @package Notes-TermControl
 * @author David M&aring;rtensson <david.martensson@gmail.com>
 * @version 1.0.0
 */
/*
Plugin Name: Notes TermControl
Plugin URI: http://notesblog.com/notes/term-control/
Description: Control which HTML tags that can be used in term descriptions. <em>(Terms are for example post tags or post descriptions).</em>
Author: David M&aring;rtensson
Version: 1.0.0
Author URI: http://www.feedmeastraycat.net/
*/


/**
 * The Notes Term Control object
 */
class NotesTermControl {
	
	
	// Config vars (set in __construct)
	public static $plugin_dir;
	public static $version;
	public static $plugin_url;
	public static $textdomain;
	public static $id;
	public static $obj;
	
	// Allowed tag vars
	public static $allowedtags = array();
	public static $system_default__allowedtags;
	
	
	
	/**
	 * Construction
	 */
	function __construct() {
		
		// Set plugin dir
		self::$plugin_dir = plugin_basename(dirname(__FILE__));
		
		// Set version
		self::$version = "1.0.0";
		
		// Set plugin url
		self::$plugin_url = WP_CONTENT_URL."/plugins/".self::$plugin_dir;
		
		// Set text domain
		self::$textdomain = "Notes-TermControl";
		
		// Set id (used as prefix for html ids, or WP options and other stuff like that)
		self::$id = "NotesTermControl";
		
		// Save "$this"
		self::$obj = $this;
		
		// Load text domain
		load_plugin_textdomain(self::$textdomain, false, self::$plugin_dir.'/languages/');
		
		// Set up allowed tags array
		self::__setUpAllowedTags();
	}
	
	
	
	// Actions
	
	/**
	 * init
	 */
	public static function init() {
		
		// Action: Save tags
		if (isset($_POST['action']) && $_POST['action'] == "update_allowed_tags") {
			self::__updateAllowedTags();
			header("Location: ".$_SERVER['PHP_SELF']."?page=".basename(__FILE__)."&updated=tags");
			exit;
		}
		
		
		/**
		 * Custom tags is on for kses
		 * @see kses.php
		 */ 
		define('CUSTOM_TAGS', true);
		
		
		// Get system default from kses.php
		global $allowedtags;
			// , $allowedposttags, $allowedentitynames
		// Save a system default copy
		self::$system_default__allowedtags = $allowedtags;
		// Set up new
		foreach (self::$allowedtags AS $tag => $attr) {
			$value = get_option(self::$id.'_tag_'.$tag, 2);
			// Allow
			if ($value == 1) {
				$allowedtags[$tag] = self::$allowedtags[$tag];
			}
			// Disallow
			elseIf ($value == 0) {
				unset($allowedtags[$tag]);
			}
			// Default
			elseIf ($value == 2) {
				// Do nothing
			}
		}
		
	}
	
	/**
	 * admin_menu
	 */
	public static function admin_menu() {
		add_options_page(
			'Notes TermControl',
			'Notes TermControl',
			'manage_options',
			basename(__FILE__),
			array(self::$obj, '__output__optionsPage')
		);
	}
	
	
	
	// Hooks
	
	/**
	 * Activation
	 */
	public static function activation_hook() {
		
		// Check installed version
		$installed_version = get_option(self::$id.'_version', '0.0.0');
		
		// No previous installed version
		if ($installed_version == "0.0.0") {
			self::__install();
		}
		
		// Require upgrade
		elseIf (version_compare($installed_version, self::$version, '<')) {
			self::__upgrade($installed_version);
		}
		
	}
	
	
	/**
	 * Deactivation
	 */
	public static function deactivation_hook() {
		
		self::__uninstall();
		
	}
	
	
	
	// Output functions
	
	/**
	 * Options page
	 */
	public static function __output__optionsPage() {
		// Get system default
		$system_default_tags = self::__getDefaultAllowedTagValue();
		?>
		<div class="wrap" id="<?=self::$id?>-options">
			<h2>Notes TermControl</h2>
			<p>	
				<?=__('Terms in WordPress are for example post tags or post categories. This plugin aims to add some extra control to them.', self::$textdomain)?>
			</p>
			<h3><?=__('Term descriptions', self::$textdomain)?></h3>
			<p>
				<?=__('Set which HTML tags you want to allow in term descriptions (i.e post tags or post categories).', self::$textdomain)?><br/>
			</p>
			<form method="post" action="<?=$_SERVER['PHP_SELF']?>?page=<?=basename(__FILE__)?>">
			<input type="hidden" name="action" value="update_allowed_tags" />
			<ul>
				<?php
				foreach (self::$allowedtags AS $tag => $attr) {
					$value = get_option(self::$id.'_tag_'.$tag, 2);
					$checked_1 = ($value == "1" ? "checked=\"checked\"":"");
					$checked_0 = ($value == "0" ? "checked=\"checked\"":"");
					$checked_2 = ($value == "2" ? "checked=\"checked\"":"");
					?>
					<li>
						<div style="float: left; width: 190px;"> 
							<input type="radio" name="<?=self::$id?>_tag_<?=$tag?>" value="1" id="<?=self::$id?>_tag_<?=$tag?>_1" <?=$checked_1?> /> <label for="<?=self::$id?>_tag_<?=$tag?>_1" style="cursor: pointer;"><?=__('Yes', self::$textdomain)?></label>
							<input type="radio" name="<?=self::$id?>_tag_<?=$tag?>" value="0" id="<?=self::$id?>_tag_<?=$tag?>_0" <?=$checked_0?> /> <label for="<?=self::$id?>_tag_<?=$tag?>_0" style="cursor: pointer;"><?=__('No', self::$textdomain)?></label>
							<input type="radio" name="<?=self::$id?>_tag_<?=$tag?>" value="2" id="<?=self::$id?>_tag_<?=$tag?>_2" <?=$checked_2?> /> <label for="<?=self::$id?>_tag_<?=$tag?>_2" style="cursor: pointer;"><?=__('Default', self::$textdomain)?></label>
							<small><em>(<?=(self::__getDefaultAllowedTagValue($tag) ? __('Yes', self::$textdomain):__('No', self::$textdomain))?>)</em></small>
						</div>
						&lt;<?=$tag?>&gt;
						<div style="clear: both;"></div>
					</li>
					<?php
				}
				?>
			</ul>
			<p>
				<em><?=__('Tags allowed by default:', self::$textdomain)?> <?=implode(", ", $system_default_tags)?></em><br/>
			</p>
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</form>			
		</div>
		<?php	
	}
	
	
	
	// Privates
	
	/**
	 * Install plugin
	 */
	private static function __install() {
		
		// Uppdate/Add version
		self::__setOption(self::$id.'_version', self::$version);
	
	}
	
	
	/**
	 * Upgrade plugin
	 */
	private static function __upgrade() {
		
		// 2.0.0 to 3.0.0 upgrade (placeholder for future use)
		/*
		if (version_compare($installed_version, '3.0.0', '<')) {
		}
		*/
		
		// Uppdate/Add version
		self::__setOption(self::$id.'_version', self::$version);
		
	}
	
	
	/**
	 * Uninstall plugin
	 */
	private static function __uninstall() {
		
		// Remove allowed tags
		foreach (self::$allowedtags AS $tag => $attr) {
			delete_option(self::$id.'_tag_'.$tag);
		}
		
		// Uppdate/Clear version
		delete_option(self::$id.'_version');
		
	}
	
	
	/**
	 * Update allowed tags
	 */
	private static function __updateAllowedTags() {
		foreach (self::$allowedtags AS $tag => $attr) {
			$value = (int)($_POST[self::$id.'_tag_'.$tag]);
			// Set 1 or 2 (allow or disallow)
			if ($value != 2) {
				self::__setOption(self::$id.'_tag_'.$tag, $value);
			}
			// Value 2 = System default = Remove option
			else {
				delete_option(self::$id.'_tag_'.$tag);
			}
		}
	}
	
	
	/**
	 * Get default value on tag. Returns true/false on one tag if tag is specified.
	 * Returns an array with allowed tags if none is specified.
	 */
	private static function __getDefaultAllowedTagValue($tag=null) {
		if (is_null($tag)) {
			$return_tags = array();
			foreach (self::$system_default__allowedtags AS $tag => $attr) {
				$return_tags[] = $tag;
			}
			return $return_tags;
		}
		else {
			if (isset(self::$system_default__allowedtags[$tag])) {
				return true;
			}
			else {
				return false;
			}
		}
	}
	
	
	/**
	 * Set up config of allowed tags. Runs when the object is created (in __construct). Gets
	 * the default allowed tags (global $allowedtags from kses.php) and adds some. The user can
	 * set allow yes/no/default on the settings page.
	 */
	private static function __setUpAllowedTags() {
		global $allowedtags;
		self::$allowedtags = $allowedtags;
		self::$allowedtags['h1'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['h2'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['h3'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['h4'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['h5'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['h6'] = array(
			'align' => array (),
			'class' => array (),
			'id'    => array (),
			'style' => array ()
		);
		self::$allowedtags['span'] = array(
			'class' => array (),
			'dir' => array (),
			'align' => array (),
			'lang' => array (),
			'style' => array (),
			'title' => array (),
			'xml:lang' => array()
		);
		self::$allowedtags['p'] = array(
			'class' => array (),
			'dir' => array (),
			'align' => array (),
			'lang' => array (),
			'style' => array (),
			'title' => array (),
			'xml:lang' => array()
		);
		self::$allowedtags['img'] = array(
			'alt' => array (),
			'align' => array (),
			'border' => array (),
			'class' => array (),
			'height' => array (),
			'hspace' => array (),
			'longdesc' => array (),
			'vspace' => array (),
			'src' => array (),
			'style' => array (),
			'width' => array ()
		);
		self::$allowedtags['div'] = array(
				'align' => array (),
				'class' => array (),
				'dir' => array (),
				'lang' => array(),
				'style' => array (),
				'xml:lang' => array()
		);
		self::$allowedtags['u'] = array();
		ksort(self::$allowedtags);
	}
	
	
	/**
	 * Adds or Updates an option
	 */
	private static function __setOption($name, $value) {
		if (get_option($name)  != $value) {
			update_option($name, $value);
		} 
  		else {
			add_option($name, $value, $deprecated='', $autoload='yes');
		}
	}
	
}

// Load up the object
$NotesTermControl = new NotesTermControl();


/**
 * Add actions
 */
add_action('init', array($NotesTermControl, 'init'));
add_action('admin_menu', array($NotesTermControl, 'admin_menu'));


/**
 * Add hooks
 */
register_activation_hook(__FILE__, array($NotesTermControl, 'activation_hook'));
register_deactivation_hook(__FILE__, array($NotesTermControl, 'deactivation_hook'));









?>