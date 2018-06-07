<?php

error_reporting(E_ERROR | E_PARSE);

$shortopts  = "";
$shortopts .= "h";
$shortopts .= "i:";

$longopts  = array(
    "help",
    "ignore:"
);

$options = getopt($shortopts, $longopts);

$colors = new Colors();

if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
	echo "Usage: " . PHP_EOL . "-h|--help: show this help" . PHP_EOL . "-i|--ignore: ignore key matching given string (wildcard allowed)". PHP_EOL;
	exit;
}

$ignore = false;

if (array_key_exists('ignore', $options)) {
	$ignore = $options["ignore"];
}else if (array_key_exists('i', $options)) {
	$ignore = $options["i"];
}

$defTransPath = "src/assets/i18n";

while (count($transFiles) == 0) {
	$transPath = readline("Please enter your translation directory (default: ".$defTransPath."): ");
	if (empty($transPath)) {
		$transPath = $defTransPath;
	}
	if (is_dir($transPath)) {
		echo "Looking for translation files in " . $transPath . "..." . PHP_EOL;
		$transFiles = glob($transPath . '/*.{json}', GLOB_BRACE);
		if (count($transFiles) == 0) {
			echo $colors->getColoredString(count($transFiles) . " translate files found", "red").PHP_EOL;
			echo "Maybe you provide the wrong directory ? You must provide the directory containing your translation files (like 'en.json', 'fr.json', etc)" . PHP_EOL . PHP_EOL;
		}
	}else {
		echo $colors->getColoredString("Invalid directory!", "red");
	}
}

echo $colors->getColoredString(count($transFiles) . " translate files found", "green").PHP_EOL;

$translate = array();

foreach($transFiles as $transFile) {
	$path = $transFile;
	
	$jsondata = file_get_contents($transFile);
	$translate = json_decode($jsondata,true);
	
	$translates[$path] = $translate;
}

foreach ($translates as $path => $trans) {
	echo $colors->getColoredString("Keys in " . $path . ": ", "green").$colors->getColoredString(count($trans), "red").PHP_EOL;
}

echo PHP_EOL;

$defSrc = "src";
$src = null;

while (!is_dir($src)) {
	$src = readline("Please enter your sources directory (default: ".$defSrc."): ");
	if (!is_dir($transPath)) {
		echo $colors->getColoredString("Invalid directory!", "red");
	}
}

echo $colors->getColoredString("Looking for unused translation key from sources in " . $src . "...", "green") . PHP_EOL;
echo PHP_EOL;

$results = array();
foreach ($translates as $path => $trans) {
	$result = array();
	$ignoredCount = 0;
	foreach ($trans as $key => $value) {
		$ignored = Util::match_string($ignore, $key);
		if ((!$ignore || $ignored == false) && Util::searchKeyOnSources($key, $src) == false) {
			$result[] = array(
				'Key' 		=> $key,
				'Value'		=> substr($value, 0, 50),
				'Line'		=> Util::findLine($key, $path)
			);
		}
		if ($ignored) $ignoredCount++;
	}
	$results[$path]["result"] = $result;
	$results[$path]["ignored"] = $ignoredCount;
}

foreach ($results as $path => $result) {
	$ascii_table = new ascii_table();
	$table = $ascii_table->make_table(
		$result["result"],
		"Unused keys in " . $path .
			" (" . $colors->getColoredString(count($result["result"])." found", count($result["result"]) > 0 ? "red" : "green") .
				($result["ignored"] > 0 ? " | " . $colors->getColoredString($result["ignored"] . " ignored", "red") : "") .
			")",
		true);
	echo $table . PHP_EOL;
}



/*****************************************
*************** Libraries ****************
******************************************/


/* Util Class*/
class Util {
	static function findLine($key, $file) {
		$search      = $key;
		$lines       = file($file);
		$line_number = false;

		while (list($key, $line) = each($lines) and !$line_number) {
			$line_number = (strpos($line, $search) !== FALSE) ? $key + 1 : $line_number;
		}

		return $line_number;
	}

	static function searchKeyOnSources($key, $src) {
		$sources = Util::getSources($src);
		foreach ($sources as $source) {
			if (Util::findLine($key, $source)) {
				return true;
			}
		}
		return false;
	}

	static function getSources($dir) {
		return  Util::glob_recursive($dir . "/*.{ts,html}", GLOB_BRACE);
	}

	static function glob_recursive($pattern, $flags = 0)
	{
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
		{
			$files = array_merge($files, Util::glob_recursive($dir.'/'.basename($pattern), $flags));
		}
		return $files;
	}

	static function match_string($pattern, $str)
	{
		$pattern = preg_replace('/([^*])/e', 'preg_quote("$1", "/")', $pattern);
		$pattern = str_replace('*', '.*', $pattern);
		return (bool) preg_match('/^' . $pattern . '$/i', $str);
	}
}

/*
	PHP ASCII Tables
	This class will convert multi-dimensional arrays into ASCII Tabled, and vise-versa.
	By Phillip Gooch <phillip.gooch@gmail.com>
*/
	class ascii_table{
    /*
        These are all the variables that the script uses to build out the table, none of them meant for user-modification
        $col_widths 	- An array that contains the max character width of each column (not including buffer spacing)
        $table_width 	- The complete width of the table, including spacing and bars
        $error 			- The error reported by file_put_contents or file_get_contents when it fails a save or load attempt.
    */
        private $col_widths = array();
        private $table_width = 0;
        public  $error = '';
    /*
        This is the function that you will call to make the table. You must pass it at least the first variable
        $array 	- A multi-dimensional array containing the data you want to build a table from.
        $title 	- The title of the table that will be centered above it, if you do not want a title you can pass a blank
        $return - The method of returning the data, this has 3 options.
            True 	- The script will return the table as a string. (Required)
            False 	- The script will echo the table out, nothing will be returned.
            String 	- It will attempt to save the table to a file with the strings name, Returning true/false of success or fail.
    */
            function make_table($array,$title='',$return=false){
        // First things first lets get the variable ready
            	$table = '';
            	$this->col_widths = array();
            	$this->table_width = 0;
        // Now we need to get some details prepared.
            	$this->get_col_widths($array);
        // If there is going to be a title we are also going to need to determine the total width of the table, otherwise we don't need it
            	if($title!=''){
            		$this->get_table_width();
            		$table .= $this->make_title($title);
            	}
        // If we have a blank array then we don't need to output anything else
            	if(isset($array[0])){
            // Now we can output the header row, along with the divider rows around it
            		$table .= $this->make_divider();
            // Output the header row
            		$table .= $this->make_headers();
            // Another divider line
            		$table .= $this->make_divider();
            // Add the table data in
            		$table .= $this->make_rows($array);
            // The final divider line.
            		$table .= $this->make_divider();
            	}
        // Now handle however you want this returned
        // First if it's a string were saving
            	if(is_string($return)){
            		$save = @file_put_contents($return,$table);
            		if($save){
            			return true;
            		}else{
                // Add the save_error if there was one
            			$this->error = 'Unable to save table to "'.$return.'".';
            			return false;
            		}
            	}else{
            // the bool returns are very simple.
            		if($return){
            			return $table;
            		}else{
            			echo $table;
            		}
            	}
            }
    /*
        This function will use the mb_strlen if available or strlen
        $value - The string that be need to be counted
        Returns a lenght of string using mb_strlen or strlen
    */
        static function len($col_value){
        	return extension_loaded('mbstring') ? mb_strlen($col_value) : strlen($col_value);
        }
    /*
        This function will load a saved ascii table and turn it back into a multi-dimensional table.
        $table 	- A PHP ASCII Table either as a string or a text file
        Returns a multi-dimensional array;
    */
        function break_table($table){
        // Try and load the file, if it fails then just return false and set an error message
        	$load_file = @file_get_contents($table);
        	if($load_file!==false){
        		$table = $load_file;
        	}
        // First thing we want to do is break it apart at the lines
        	$table = explode(PHP_EOL,trim($table));
        // Check if the very first character of the very first row is a +, if not delete that row, it must be a title.
        	if(substr($table[0],0,1)!='+'){
        		unset($table[0]);
        		$table = array_values($table);
        	}
        // Were going to need a few variables ready-to-go, so lets do that
        	$array = array();
        	$array_columns = array();
        // Now we want to grab row [1] and get the column names from it.
        	$columns = explode('|',$table[1]);
        	foreach($columns as $n => $value){
            // The first and last columns are blank, so lets skip them
        		if($n>0 && $n<count($columns)-1){
                // If we have a value after trimming the whitespace then use it, otherwise just give the column it's number as it's name
        			if(trim($value)!=''){
        				$array_columns[$n] = trim($value);
        			}else{
        				$array_columns[$n] = $n;
        			}
        		}
        	}
        // And now we can go through the bulk of the table data
        	for($row=3;$row<count($table)-1;$row++){
            // Break the row apart on the pipe as well
        		$row_items = explode('|',$table[$row]);
            // Loop through all the array columns and grab the appropriate value, placing it all in the $array variable.
        		foreach($array_columns as $pos => $column){
                // Add the details into the main $array table, remembering to trim them of that extra whitespace
        			$array[$row][$column] = trim($row_items[$pos]);
        		}
        	}
        // Reflow the array so that it starts at the logical 0 point
        	$array = array_values($array);
        // Return the array
        	return $array;
        }
    /*
        This will take a table in either a file or a string and scrape out two columns of data from it. If you only pass a single column it will
        return that in a straight numeric array.
        $table - The table file or string
        $key - They column to be used as the array key, if no value is passed, the value that will be placed in the numeric array.
        $value - the column to be used as the array value, if null then key will be returned in numeric array.
    */
        function scrape_table($table,$key,$value=null){
        // First things first wets parse the entire table out.
        	$table = $this->break_table($table);
        // Set up a variable to store the return in while processing
        	$array = array();
        // Now we loop through the table
        	foreach($table as $row => $data){
            // If value is null then set it to key and key to row.
        		if($value==null){
        			$grabbed_value = $data[$key];
        			$grabbed_key = $row;
                // Else just grab the desired key/value values
        		}else{
        			$grabbed_key = $data[$key];
        			$grabbed_value = $data[$value];
        		}
            // Place the information into the array().
        		$array[$grabbed_key] = $grabbed_value;
        	}
        // Finally return the new array
        	return $array;
        }
    /*
        This class will set the $col_width variable with the longest value in each column
        $array 	- The multi-dimensional array you are building the ASCII Table from
    */
        function get_col_widths($array){
        // If we have some array data loop through each row, then through each cell
        	if(isset($array[0])){
        		foreach(array_keys($array[0]) as $col){
                // Get the longest col value and compare with the col name to get the longest
        			$this->col_widths[$col] = max(max(array_map(array($this,'len'), $this->arr_col($array, $col))), $this->len($col));
        		}
        	}
        }
    /*
        This is an array_column shim, it will use the PHP array_column function if there is one, otherwise it will do the same thing the old way.
    */
        function arr_col($array,$col){
        	if(is_callable('array_column')){
        		return array_column($array,$col);
        	}else{
        		$return = array();
        		foreach($array as $n => $dat){
        			if(isset($dat[$col])){
        				$return[] = $dat[$col];
        			}
        		}
        		return $return;
        	}
        }
    /*
        This will get the entire width of the table and set $table_width accordingly. This value is used when building.
    */
        function get_table_width(){
        // Add up all the columns
        	$this->table_width = array_sum($this->col_widths);
        // Add in the spacers between the columns (one on each side of the value)
        	$this->table_width += count($this->col_widths)*2;
        // Add in the dividers between columns, as well as the ones for the outside of the table
        	$this->table_width += count($this->col_widths)+1;
        }
    /*
        This will return the centered title (only called if a title is passed)
    */
        function make_title($title){
        // First we want to remove any extra whitespace for a proper centering
        	$title = trim($title);
        // Determine the padding needed on the left side of the title
        	$left_padding = floor(($this->table_width-$this->len($title))/2);
        // return exactly what is needed
        	return str_repeat(' ',max($left_padding,0)).$title.PHP_EOL;
        }
    /*
        This will use the data in the $col_width var to make a divider line.
    */
        function make_divider(){
        // were going to start with a simple union piece
        	$divider = '+';
        // Loop through the table, adding lines of the appropriate length (remembering the +2 for the spacers), and a union piece at the end
        	foreach($this->col_widths as $col => $length){
        		$divider .= str_repeat('-',$length+2).'+';
        	}
        // return it
        	return $divider.PHP_EOL;
        }
    /*
        This will look through the $col_widths array and make a column header for each one
    */
        function make_headers(){
        // This time were going to start with a simple bar;
        	$row = '|';
        // Loop though the col widths, adding the cleaned title and needed padding
        	foreach($this->col_widths as $col => $length){
            // Add title
        		$row .= ' '.$col.' ';
            // Check and see if we need additional padding, if so go ahead and add it
        		if($this->len($col)<$length){
        			$row .= str_repeat(' ',$length-$this->len($col));
        		}
            // Add the right hand bar
        		$row .= '|';
        	}
        // Return the row
        	return $row.PHP_EOL;
        }
    /*
        This makes the actual table rows
        $array 	- The multi-dimensional array you are building the ASCII Table from
    */
        function make_rows($array){
        // Just prep the variable
        	$rows = '';
        // Loop through rows
        	foreach($array as $n => $data){
            // Again were going to start with a simple bar
        		$rows .= '|';
            // Loop through the columns
        		foreach($data as $col => $value){
                // Add the value to the table
        			$rows .= ' '.$value.' ';
                // check and see if that value needs any padding, if so add it
        			if($this->len($value)<$this->col_widths[$col]){
        				$rows .= str_repeat(' ', $this->col_widths[$col]-$this->len($value));
        			}
                // Add the right hand bar
        			$rows .= '|';
        		}
            // Add the row divider
        		$rows .= PHP_EOL;
        	}
        // Return the row
        	return $rows;
        }
    }

    class Colors {
		private $foreground_colors = array();
		private $background_colors = array();

		public function __construct() {
			// Set up shell colors
			$this->foreground_colors['black'] = '0;30';
			$this->foreground_colors['dark_gray'] = '1;30';
			$this->foreground_colors['blue'] = '0;34';
			$this->foreground_colors['light_blue'] = '1;34';
			$this->foreground_colors['green'] = '0;32';
			$this->foreground_colors['light_green'] = '1;32';
			$this->foreground_colors['cyan'] = '0;36';
			$this->foreground_colors['light_cyan'] = '1;36';
			$this->foreground_colors['red'] = '0;31';
			$this->foreground_colors['light_red'] = '1;31';
			$this->foreground_colors['purple'] = '0;35';
			$this->foreground_colors['light_purple'] = '1;35';
			$this->foreground_colors['brown'] = '0;33';
			$this->foreground_colors['yellow'] = '1;33';
			$this->foreground_colors['light_gray'] = '0;37';
			$this->foreground_colors['white'] = '1;37';

			$this->background_colors['black'] = '40';
			$this->background_colors['red'] = '41';
			$this->background_colors['green'] = '42';
			$this->background_colors['yellow'] = '43';
			$this->background_colors['blue'] = '44';
			$this->background_colors['magenta'] = '45';
			$this->background_colors['cyan'] = '46';
			$this->background_colors['light_gray'] = '47';
		}

		// Returns colored string
		public function getColoredString($string, $foreground_color = null, $background_color = null) {
			$colored_string = "";

			// Check if given foreground color found
			if (isset($this->foreground_colors[$foreground_color])) {
				$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
			}
			// Check if given background color found
			if (isset($this->background_colors[$background_color])) {
				$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
			}

			// Add string and end coloring
			$colored_string .=  $string . "\033[0m";

			return $colored_string;
		}

		// Returns all foreground color names
		public function getForegroundColors() {
			return array_keys($this->foreground_colors);
		}

		// Returns all background color names
		public function getBackgroundColors() {
			return array_keys($this->background_colors);
		}
	}