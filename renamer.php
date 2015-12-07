<?php
/**
 * WPPB Renamer
 * 
 * The main purpose of this script is to easen WordPress plugin developpement
 * by providing a quick way to rename plugins or themes.
 * 
 * WPPB Renamer is primarily aimed to be used to customize a fresh 
 * sample of the WordPres Plugin Boilerplate (for instance, when you clone
 * or download the project right from GitHub) and do all the file renaming
 * and strings renaming task. 
 * 
 * If you are satisfied with the original WPPB, then you can already use 
 * the excellent http://wppb.me/ web app that furfill the need perfectly.
 * 
 * But when you start to customize your own fork of the boilerplate, then
 * wppb.me can't do it and you were back to the beginnig, starting your 
 * projects with a repetitive search and replace session... 
 * 
 * This was until today! Now you have more options : 
 * 
 * - Command-line interface locally with php-cli;
 * - Command-line interface to your server via SSH and php-cli;
 * - Good old $_GET method;
 * 
 * Shell usage : `php renamer.php <options>`
 * Ex. (default mode, step-by-step): php renamer.php
 * Ex. (with params specified): php renamer.php -ini "Initial Name" -new "New Name" ...
 * 
 * HTTP usage : `/folder-name/renamer.php`
 * Ex. (default mode): http://site.loc/wp-content/plugins/plugin-name/renamer.php
 * Ex. (with params specified): renamer.php?initial_name=Initial+Name&new_name=New+Name& ...
 * 
 * Forked and improved by webmarka (Marc-Antoine Minville)
 * Github : https://github.com/webmarka/wp-boilerplate-renamer
 * Inspired from an original script by eugen (5/4/15 10:59 PM) 
 * Github : https://github.com/EugenBobrowski/wp-boilerplate-renamer
 * Special thanks to eugen for having starting this little project :)
 */

class WPPB_Renamer {
	
	/**
	 * @var string
	 */
	private $origin_file;
	
	/**
	 * @var string
	 */
	public $search;
	
	
	/**
	 * @var string
	 */
	public $replace;
	
	/**
	 * @var array
	 */
	public $results = array();
	
	
	/**
	 * @var array. Keys are searched. Values are replaced.
	 */
	public $replace_array;
	
	
	/**
	 * @var int
	 */
	public $renamed_files_count = 0;
	
	/**
	 * @var int
	 */
	public $replaced_text_count = 0;
	
	
	public function __construct($origin_file) {
		
		$this->origin_file = $origin_file;
	}
	
	
	/*
	 * Generate replacement array.
	 * 
	 * name: generate_replace_array
	 * 
	 * @param
	 * @return
	 * 
	 */
	public function generate_replace_array() {
		
		$this->search = strtolower($this->search);
		$this->replace = strtolower($this->replace);
		$replace_array = array(
				//    'twenty fifteen' => 'twenty something',
				$this->search => $this->replace,
		);
		
		$search_exploded_lc = explode(' ', $this->search);
		$replace_exploded_lc = explode(' ', $this->replace);

		$search_exploded_ucfirst = explode(' ', ucwords($this->search));
		$replace_exploded_ucfirst = explode(' ', ucwords($this->replace));
		
		//    'Twenty Fifteen' => 'Twenty Something',
		$replace_array[implode(' ', $search_exploded_ucfirst)] = implode(' ', $replace_exploded_ucfirst);
		//    'TwentyFifteen' => 'TwentySomething',
		$replace_array[implode('_', $search_exploded_ucfirst)] = implode('_', $replace_exploded_ucfirst);
		//    'Twenty_Fifteen' => 'Twenty_Something',
		$replace_array[implode('_', $search_exploded_ucfirst)] = implode('_', $replace_exploded_ucfirst);
		//    'twentyfifteen' => 'twentysomething',
		$replace_array[implode('',$search_exploded_lc)] = implode('',$replace_exploded_lc);
		//    'twenty-fifteen' => 'twenty-something',
		$replace_array[implode('-',$search_exploded_lc)] = implode('-',$replace_exploded_lc);
		//    'twenty_fifteen' => 'twenty_something',
		$replace_array[implode('_',$search_exploded_lc)] = implode('_',$replace_exploded_lc);
		//    'TWENTY_FIFTEEN' => 'TWENTY_SOMETHING',
		$replace_array[strtoupper(implode('_',$search_exploded_lc))] = strtoupper(implode('_',$replace_exploded_lc));

		$this->add_replacement($replace_array);
	}
	
	
	/*
	 * Add replacement terms list.
	 * 
	 * name: add_replacement
	 * 
	 * @param $replace_array
	 * @return
	 * 
	 */
	public function add_replacement($replace_array) {
		
		$this->replace_array = array_merge($this->replace_array, $replace_array);
	}
	
	
	public function get_temp_replace($string) {
		
		return md5('__TEMP__'.$string.'__');
	}
	
	
	public function validate_user_input($string, $default='') {
		
		$validated = preg_replace("/[^ \w]+/", "", trim(escapeshellarg($string)));
		
		if (empty($validated) || !is_string($validated)) $validated = $default;
		
		return $validated;
	}
	
	
	/*
	 * 
	 * name: str_replace_custom
	 * @param $content string
	 * @return string
	 * 
	 */
	public function str_replace_custom ($content) {
		
		$excluded_strings = array(
			'* Plugin Name:',
			'->plugin_name',
			'$plugin_name',
			'get_plugin_name',
		);
		
		// Protect excluded strings.
		foreach ($excluded_strings as $protect) {
			
			$content = str_replace($protect, $this->get_temp_replace($protect), $content);
		}
		
		// Perform main replacements.
		foreach ($this->replace_array as $search=>$replace) {
			
			$content = str_replace($search, $replace, $content);
		}
		
		// Reset excluded strings.
		foreach ($excluded_strings as $original) {
			
			$content = str_replace($this->get_temp_replace($original), $original, $content);
		}
		
		return $content;
	}
	
	
	/*
	 * Scan a directory and rename files.
	 * 
	 * name: scanTheDir
	 * 
	 * @param $path
	 * @return
	 * 
	 */
	public function scanTheDir($path) {
		
		$items = scandir($path);
		$excluded_files = array('.', '..', basename($this->origin_file));
		
		foreach ($items as $item) {
			
			$path_item = $path.'/'.$item;
			
			if (in_array($item, $excluded_files)) {
				
				/*
				$this->results[$path_item] = array(
					'item'			=>		basename(__FILE__), 
					'is_dir'		=>		is_dir($path_item),
					'modified'	=>		false, 
					'excluded'	=>		true, 
					'message'		=>		'Excluded file.',
				);
				*/
			} elseif (realpath($path_item) == __FILE__ ) {
				
				$this->results[$path_item] = array(
					'item'			=>		basename(__FILE__), 
					'new_name'	=>		null,
					'is_dir'		=>		false,
					'modified'	=>		false, 
					'excluded'	=>		true, 
					'message'		=>		'Current file.',
				);
				
			} elseif (is_dir($path_item)) {
				
				$renamefiles = $this->renamefiles($path, $item);
				
				$this->results[$path_item] = array(
					'item'			=>		$item, 
					'new_name'	=>		$renamefiles['modified'] ? $renamefiles['new_name']: null,
					'is_dir'		=>		true,
					'modified'	=>		$renamefiles['modified'], 
					'excluded'	=>		false, 
					'message'		=>		$renamefiles['modified'] ? '[REPLACED] > '.$renamefiles['new_name']: '',
				);
				
				if ($renamefiles['modified']) $this->renamed_files_count++;
				
				$this->scanTheDir($renamefiles['path_item']);
				
			} else {
				
				$renamefiles = $this->renamefiles($path, $item);
				$new_path_item = $renamefiles['path_item'];
				
				$this->results[$path_item] = array(
					'item'			=>		$item, 
					'new_name'	=>		$renamefiles['modified'] ? $renamefiles['new_name']: null,
					'is_dir'		=>		false,
					'modified'	=>		$renamefiles['modified'], 
					'excluded'	=>		false, 
					'message'		=>		$renamefiles['modified'] ? '[REPLACED] > '.$renamefiles['new_name']: '',
				);
				
				if ($renamefiles['modified']) $this->renamed_files_count++;
				
				$content = file_get_contents($new_path_item);
				$new_content = $this->str_replace_custom($content);
				
				if ($new_content != $content && file_put_contents($new_path_item, $new_content)) {
						$this->results[$path_item]['message'] .= '[REPLACED]';
						$this->results[$path_item]['modified'] = true;
						$this->replaced_text_count++;
				}
				
			}
			$new_name = $this->results[$path_item]['new_name'];
			$new_name = empty($new_name) ? $item: $new_name;
			$this->results[$path_item]['new_name'] = $new_name;
		}
		
		return $this->results;		
	}
	
	
	public function display_results($shell = false, $verbose = false) {
		
		// Initialize local variables.
		$output = "";
		
		// Prepare output.
		$output .= $shell ? $this->shell_text("\n"): "<ul>";
		
		foreach ($this->results as $path => $infos) {
			
			$before = $after = "";
			
			// Check conditions.
			if ($infos['excluded'] === true) {
				
				$color = $infos['modified'] ? 'blue': 'white';
				
			} elseif ($infos['is_dir'] === true) {
				
				$color = $infos['modified'] ? 'blue': 'white';
				$before = $shell ? " ": "";
				
			} else {
				
				$color = $infos['modified'] ? 'blue': 'white';
				$before = $shell ? "\t": "";
			}
			
			$final_item = $infos['modified'] ? $infos['new_name']: $infos['item'];
			
			// Prepare line output.
			if ($shell) {
					if ($verbose && !empty($infos['item'])) $output .= $before.$this->shell_text($infos['item'], $color)." ".$infos['message']."\n";
					elseif ($infos['excluded'] !== true && !empty($infos['item'])) $output .= $before.$this->shell_text($final_item, $color)."\n";
			} else {
					$final_item = $infos['is_dir'] ? "<b>".$final_item."</b>": $final_item;
					if ($verbose && !empty($infos['item'])) $output .= "<li style=\"color:{$color};\">".$infos['item']." ".$infos['message']."</li>";
					elseif ($infos['excluded'] !== true && !empty($infos['item'])) $output .= "<li style=\"color:{$color};\">".$final_item."</li>";
			}
		}
		
		$output .= $shell ? $this->shell_text("\n"): "</ul>";
		
		
		print $shell ? "\n --------------------------------------------- \n": "<br>";
		
		print $shell ? "  Replacement Report \n": " Replacement Report ";
		
		print $shell ? " --------------------------------------------- \n": "<br>";
		
		print $output;
		
		print $shell ? " --------------------------------------------- \n\n": "<br>";
		
		if ((int) $this->replaced_text_count + (int) $this->replaced_text_count >= 1) {
			$color = 'green';
			print $shell ? $this->shell_text(" Successfully renamed plugin! \n", $color): "<p style=\"color:{$color};\"><b> Successfully renamed plugin! </b></p>";
		} else {
			$color = 'yellow';
			print $shell ? $this->shell_text(" No replacement occurences found! \n", $color): "<p style=\"color:{$color};\"><b> No replacement occurences found! </b></p>";
		}
		print $shell ? " Renamed files count: ".$this->shell_text("{$this->renamed_files_count} \n", $color): "<p> Renamed files count: <b style=\"color:{$color};\">".$this->renamed_files_count."</b></p>";
		print $shell ? " Replaced text count: ".$this->shell_text("{$this->replaced_text_count} \n", $color): "</p> Replaced text count: <b style=\"color:{$color};\">".$this->replaced_text_count."</b></p>";
		
		print $shell ? "\n --------------------------------------------- \n\n": "<br>";

	}
	
	
	
	/*
	 * Rename files and output result
	 * 
	 * name: renamefiles
	 * 
	 * @param $path
	 * @param $item
	 * @return string
	 * 
	 */
	public function renamefiles($path, $item) {
		
		$message = '';
		$new_name = $this->str_replace_custom($item);
		
		if ($new_name != $item && rename($path."/".$item, $path."/".$new_name)) {
				
				$modified = true;
				$path_item = $path."/".$new_name;
				
		} else {
				
				$modified = false;
				$path_item = $path."/".$item;
		}
		
		return array('path_item'=>$path_item, 'modified'=>$modified, 'new_name'=>$new_name);
	}
	
	
	/*
	 * Display command-line help.
	 * 
	 * name: commandline_help
	 * @return	string
	 * 
	 */
	function commandline_help() {
		
	/* *
	 * @link              http://example.com
	 * @since             1.0.0
	 * @package           My_Plugin
	 *
	 * @wordpress-plugin
	 * Plugin Name:       My Plugin
	 * Plugin URI:        http://example.com/my-plugin-uri/
	 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
	 * Version:           1.0.0
	 * Author:            Your Name or Your Company
	 * Author URI:        http://example.com/
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * Text Domain:       my-plugin
	 * Domain Path:       /languages
	 * */
	 
		print "\n Usage: php rename.php <options>\n\n";
		
		print " Options:\n";
		
		print " -hello (optional)\t\t Do nothing!\n";
		print " -ini, --initial-name (optional)\t\t Initial name of plugin (the string to search). Default: Plugin Name. Min. 4 chars. Allowed characters: letters, numbers, spaces, `-` and `_`. \n";
		print " -new, --new-name (optional)\t\t New name for the plugin (the replacement string). Default: Plugin Name 2. Allowed characters: same than -i.\n";
		print " -add, --add-replacements (optional)\t\t Add custom replacements. Default (built-in): WordPress Plugin Boilerplate. Allowed characters: same than -i. Syntax: Term 1|Replacement 1,Term 2|Replacement 2,Replace This By New Name \n";
		
		print " -l, --link (optional)\t\t Main link.\n";
		print " -puri, --plugin-uri (optional)\t\t Plugin URI.\n";
		print " -author, (optional)\t\t Plugin Author.\n";
		print " -auri, --author-uri, (optional)\t\t Author URI.\n";
		print " -desc, --description, (optional)\t\t Plugin Description.\n";
		
		print " -h, --help\t\t Help manual!\n";
	}
	
	
	/*
	 * Format string for shell display.
	 * 
	 * name: shell_text
	 * @param
	 * @return
	 * 
	 */
	function shell_text($text, $color='default') {
		
		/*
		 * 
			black - 30
			red - 31
			green - 32
			brown - 33
			blue - 34
			magenta - 35
			cyan - 36
			lightgray - 37
			* 
			\033[0m - is the default color for the console
			\033[0;#m - is the color of the text, where # is one of the codes mentioned above
			\033[1m - makes text bold
			\033[1;#m - makes colored text bold**
			\033[2;#m - colors text according to # but a bit darker
			\033[4;#m - colors text in # and underlines
			\033[7;#m - colors the background according to #
			\033[9;#m - colors text and strikes it
			\033[A - moves cursor one line above (carfull: it does not erase the previously written line)
			\033[B - moves cursor one line under
			\033[C - moves cursor one spacing to the right
			\033[D - moves cursor one spacing to the left
			\033[E - don't know yet
			\033[F - don't know yet
			* 
		*/
		
		$color = strtolower($color);
		
		switch($color) {
			
			case 'red':
			 $code = "01;31m";
			break;
			
			case 'green':
			 $code = "01;32m";
			break;
			
			case 'yellow':
			 $code = "01;33m";
			break;
			
			case 'blue':
			 $code = "01;34m";
			break;
			
			case 'cyan':
			 $code = "01;36m";
			break;
		
			case 'white':
			 $code = "1m";
			break;
			
			case 'lightgray':
			case 'gray':
			 $code = "02;37m";
			break;
			
			default:
				$code = "00m";
		}
		
		$output = "\033[{$code}{$text}\033[0m";
		
		return $output;
	}

} // End of class.


// Display errors.
error_reporting (E_ALL ^ E_NOTICE ^ E_WARNING);

// Initialize Extension Renamer instance.
$renamer = new WPPB_Renamer(__FILE__);

// Defaults values.
$default = new stdClass;
$default->initial_name = "Plugin Name";
$default->new_name = "Plugin Name 2";

$default->link = "http://example.com";
$default->plugin_uri = "http://example.com/plugin-name-uri/";
$default->author = "Your Name or Your Company";
$default->author_uri = "http://example.com/";
$default->description = "This is a short description of what the plugin does. It's displayed in the WordPress admin area.";
$default->add_replacements = "WordPress Plugin Boilerplate";
#$default->KEY = "VALUE";

// Check for shell args.
$command_line = 0;

if (isset($_SERVER['argv']) && $_SERVER['argc'] >= 2) {
	
	// Shell command with options.
	
	$command_line = 1;
	$ac = 1; //argument counter
	
	while ($ac < (count($_SERVER['argv']))) {
		
		$arg = $_SERVER['argv'][$ac];

		if ($arg  === '-hello') {
			// Nothing for now.
			
		} else if ($arg === '-ini' || $arg === '--initial-name') {
			
			$initial_name = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
			
		} else if ($arg === '-new' || $arg === '--new-name') {
			
			$new_name = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
			
		} else if ($arg === '-link') {
			
			$link = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
			
		} else if ($arg === '-puri' || $arg === '--plugin-uri') {
			
			$plugin_uri = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
		} else if ($arg === '-auri' || $arg === '--author-uri') {
			
			$author_uri = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
		} else if ($arg === '-desc' || $arg === '--description') {
			
			$description = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
			
		} else if ($arg === '-author') {
			
			$author = $_SERVER['argv'][$ac+1];
			$ac = $ac+2;
			
		} else if ($arg === '-a' || $arg === '--add-replacements') {
			
			$add_replacements = $_SERVER['argv'][$ac+1];
			//$add_replacements = explode(',', $add_replacements);
			$ac = $ac+2;
			
		} else {
			$renamer->commandline_help();
			die();
		}
	}
	
	// Validate user input.
	$initial_name = $renamer->validate_user_input($initial_name, $default->initial_name);
	$new_name = $renamer->validate_user_input($new_name, $default->new_name);
	$link = $renamer->validate_user_input($link, $default->link);
	$plugin_uri = $renamer->validate_user_input($plugin_uri, $default->plugin_uri);
	$author = $renamer->validate_user_input($author, $default->author);
	$author_uri = $renamer->validate_user_input($author_uri, $default->author_uri);
	$description = $renamer->validate_user_input($description, $default->description);
	$add_replacements = $renamer->validate_user_input($add_replacements, $default->add_replacements);
	
	// Got to step 3.
	goto Step3;
	
} else {
	
	// Got to step 1.
	goto Step1;
}

// Shell command without options : step-by-step process.

// Step 1 : Initial name and New name.
Step1:
$color = "gray";

// initial_name
Step1_A:
$msg_step_1_A = "\n Enter original plugin name. Leave empty to keep default `".$renamer->shell_text($default->initial_name, $color)."` : \n\033[C";
print($msg_step_1_A);
$initial_name = $renamer->validate_user_input(fgets(STDIN), $default->initial_name);
$msg_step_1_A_result = " You entered : ".$renamer->shell_text($initial_name, 'green')."\n\n";
print($msg_step_1_A_result);
if (count_chars($initial_name) < 4) {
	print("Minimum 4 chars."); goto Step1_A;
}

// new_name
Step1_B:
$msg_step_1_B = "\n Enter new plugin name. Leave empty to keep default `".$renamer->shell_text($default->new_name, $color)."` : \n\033[C";
print($msg_step_1_B);
$new_name = $renamer->validate_user_input(fgets(STDIN), $default->new_name);
$msg_step_1_B_result = " You entered : ".$renamer->shell_text($new_name, 'green')."\n\n";
print($msg_step_1_B_result);
if (count_chars($new_name) < 4) {
	print(" Minimum 4 chars."); goto Step1_B;
}

// Step 2 : Additional params.
Step2:

// Step 2 A : Confirm specify additional params.
Step2_A:
$msg_step_2_start .= "\n Press ".$renamer->shell_text('Y', 'white')." to specify additional params, or any other key to keep defaults. \n\033[C";
print($msg_step_2_start);
$confirm = fgets(STDIN);
$confirm = trim(strtolower($confirm));
if (empty($confirm) || $confirm !== "y") {
	
	// Go to Step3 and skip additionnal params.
	goto Step3;
}

// link
Step2_B:
$msg_step_2_B = "\n Enter link. Leave empty to keep default `".$renamer->shell_text($default->link, $color)."` : \n\033[C";
print($msg_step_2_B);
$link = fgets(STDIN);
$link = !empty(trim($link)) ? trim($link): $default->link;
$msg_step_2_B_result = " You entered : ".$renamer->shell_text($link, 'green')."\n\n";
print($msg_step_2_B_result);
if (count_chars($link) < 4) {
	print(" Minimum 4 chars."); goto Step2_B;
}

// plugin_uri
Step2_C:
$msg_step_2_C = "\n Enter plugin URI. Leave empty to keep default `".$renamer->shell_text($default->plugin_uri, $color)."` : \n\033[C";
print($msg_step_2_C);
$plugin_uri = fgets(STDIN);
$plugin_uri = !empty(trim($plugin_uri)) ? trim($plugin_uri): $default->plugin_uri;
$msg_step_2_C_result = " You entered : ".$renamer->shell_text($plugin_uri, 'green')."\n\n";
print($msg_step_2_C_result);
if (count_chars($plugin_uri) < 4) {
	print(" Minimum 4 chars."); goto Step2_C;
}

// author
Step2_D:
$msg_step_2_D = "\n Enter author. Leave empty to keep default `".$renamer->shell_text($default->author, $color)."` : \n\033[C";
print($msg_step_2_D);
$author = fgets(STDIN);
$author = !empty(trim($author)) ? trim($author): $default->author;
$msg_step_2_D_result = " You entered : ".$renamer->shell_text($author, 'green')."\n\n";
print($msg_step_2_D_result);
if (count_chars($author) < 4) {
	print(" Minimum 4 chars."); goto Step2_D; 
}

// author_uri
Step2_E:
$msg_step_2_E = "\n Enter author URI. Leave empty to keep default `".$renamer->shell_text($default->author_uri, $color)."` : \n\033[C";
print($msg_step_2_E);
$author_uri = fgets(STDIN);
$author_uri = !empty(trim($author_uri)) ? trim($author_uri): $default->author_uri;
$msg_step_2_E_result = " You entered : ".$renamer->shell_text($author_uri, 'green')."\n\n";
print($msg_step_2_E_result);
if (count_chars($author_uri) < 4) {
	print(" Minimum 4 chars."); goto Step2_E;
}

// description
Step2_F:
$msg_step_2_F = "\n Enter description. Leave empty to keep default `".$renamer->shell_text($default->description, $color)."` : \n\033[C";
print($msg_step_2_F);
$description = fgets(STDIN);
$description = !empty(trim($description)) ? trim($description): $default->description;
$msg_step_2_F_result = " You entered : ".$renamer->shell_text($description, 'green')."\n\n";
print($msg_step_2_F_result);
if (count_chars($description) < 4) {
	print(" Minimum 4 chars."); goto Step2_F;
}

// add_replacements
Step2_G:
$msg_step_2_G = "\n Enter additionnal replacements. Leave empty to keep default `".$renamer->shell_text($default->add_replacements, $color)."` : \n\033[C";
print($msg_step_2_G);
$add_replacements = fgets(STDIN);
$add_replacements = !empty(trim($add_replacements)) ? trim($add_replacements): $default->add_replacements;
$msg_step_2_G_result = " You entered : ".$renamer->shell_text($add_replacements, 'green')."\n\n";
print($msg_step_2_G_result);
if (count_chars($add_replacements) < 4) {
	print(" Minimum 4 chars."); goto Step2_G;
}

// Step 3 : Rename process.
Step3:

// Validate user input.
#$initial_name = $renamer->validate_user_input($initial_name, $default->initial_name);
#$new_name = $renamer->validate_user_input($new_name, $default->new_name);
$link = !empty($link) ? $renamer->validate_user_input($link, $default->link): $default->link;
$plugin_uri = !empty($plugin_uri) ? $renamer->validate_user_input($plugin_uri, $default->plugin_uri): $default->plugin_uri;
$author = !empty($author) ? $renamer->validate_user_input($author, $default->author): $default->author;
$author_uri = !empty($author_uri) ? $renamer->validate_user_input($author_uri, $default->author_uri): $default->author_uri;
$description = !empty($description) ? $renamer->validate_user_input($description, $default->description): $default->description;
$add_replacements = !empty($add_replacements) ? $renamer->validate_user_input($add_replacements, $default->add_replacements): $default->add_replacements;

if (!empty($initial_name) && is_string($initial_name) && !empty($new_name) && is_string($new_name)) {
	
	$msg_step_3_start = "";
	$msg_step_3_start .= " \n";
	$msg_step_3_start .= " *********************************************\n";
	$msg_step_3_start .= " *********** KJM Extension Renamer ***********\n";
	$msg_step_3_start .= " *********************************************\n";
	$msg_step_3_start .= " \n";
	$msg_step_3_start .= " Initial name: \t\t".$renamer->shell_text($initial_name, 'blue')."\n";
	$msg_step_3_start .= " New name: \t\t".$renamer->shell_text($new_name, 'blue')."\n";
	
	$msg_step_3_start .= " Add Replacements: \t".$renamer->shell_text($add_replacements, 'blue')."\n";
	$msg_step_3_start .= " Link: \t\t\t".$renamer->shell_text($link, 'blue')."\n";
	$msg_step_3_start .= " Plugin URI: \t\t".$renamer->shell_text($plugin_uri, 'blue')."\n";
	$msg_step_3_start .= " Author: \t\t".$renamer->shell_text($author, 'blue')."\n";
	$msg_step_3_start .= " Author URI: \t\t".$renamer->shell_text($author_uri, 'blue')."\n";
	$msg_step_3_start .= " Description: \t\t".$renamer->shell_text($description, 'blue')."\n";
	#$msg_step_3_start .= " LABEL: \t\t".$renamer->shell_text($VAR, 'blue')."\n";
	
	$msg_step_3_start .= " \n";
	$msg_step_3_start .= " *********************************************\n";
	$msg_step_3_start .= " \n";
	$msg_step_3_start .= " Press ".$renamer->shell_text('Y', 'white')." to continue, or any other key to abort. \n\033[C";
	print($msg_step_3_start);
	
	// Step 4 : Confirm rename process.
	Step4:
	$confirm = fgets(STDIN);
	$confirm = trim(strtolower($confirm));
	if (empty($confirm) || $confirm !== "y") {
		print $renamer->shell_text(" Operation aborted. \n\n\033[C", "red"); die();
	}
	
	
	// Set params.
	$renamer->replace_array = array();
	$renamer->search = $initial_name;
	$renamer->replace = $new_name;
	$renamer->generate_replace_array();
	
	// Add custom replacements.
	$add_replacements = explode(',', $add_replacements);
	foreach($add_replacements as $replacement) {
		
		$add_replacement = explode('|', $replacement);
		if (!empty($add_replacement[0]))
		$renamer->add_replacement(array(
				$add_replacement[0] => empty($add_replacement[1]) ? $new_name: $add_replacement[1],
		));
	}
	
	// Run the renaming process
	$renamer->scanTheDir('./');
	$renamer->display_results(true);
} else {
	// Something is missing, return to step 1.
	goto Step1;
}
