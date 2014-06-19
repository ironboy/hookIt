<?php
/*
	The hook it module.

	Nodebite 2014, Thomas Frank

	This module does nothing in itself.
	It is a  helper module that simplifies listening
	to hooks when you write class based code in other modules

*/

class HookIt {

	/*
		hookIt(array(
			"init" => "bark, eat"
			"boot" => "eat"
		));

	*/

	private static $hookMem = array();

	protected function hook_it($hookSettings){
		$this->hookIt($hookSettings);
	}

	protected function hookIt($hookSettings){
		$module = $this->currentModule();
		foreach ($hookSettings as $hook => $methods){
			$methods = explode(",",str_replace(" ","",$methods));
			foreach($methods as $method){
				HookIt::regHook($module,$hook,$method,$this);
			}
		}
	}

	protected function currentModule(){
		return "cool";
		return drupal_get_current_module_name();
	}

	public static function regHook($module,$hook,$method,$obj){

		$mem = &self::$hookMem;

		// Add module to memory
		if(!isset($mem)){
			$mem[$module] = array();
		}

		// Add hook to memory and create a function for it
		if(!isset($mem[$module][$hook])){
			$mem[$module][$hook] = array();
			// Params by ref
			$params = "";
			for($i = 0; $i < 100; $i++){
				$params.= ($i ? ',' : '').'&$param'.$i. ' = null ';
			}
			// And a function for the hook
			eval(
				"function ".$module."_".$hook.'('.$params.'){'.
				'$argc = func_num_args();$params = array();'.
					'for ($i = 0; $i < $argc; $i++) {'.
					'$name = "param".$i;'.
					'$params[] = & $$name;}'.
				'HookIt::hookLookup'.
				'("'.$module.'","'.$hook.'",$params);}'
			);
		}

		// Add method to memory
		if(!isset($mem[$module][$hook][$method])){
				$mem[$module][$hook][] = array(
					"obj" => $obj,
					"method" => $method
				);
		}

	}

	public static function hookLookup($moduleName,$hookName,$args){

		$mem = self::$hookMem;

		// Don't attempt anything if not registrered properly
		if(!isset($mem[$moduleName]) || !isset($mem[$moduleName][$hookName])){
			return;
		}

		// Call registrered classes and methods
		foreach($mem[$moduleName][$hookName] as $method){
			
			if(!method_exists($method["obj"],$method["method"])){
				continue;
			}

			call_user_func_array(
				array($method["obj"],$method["method"]),
				$args
			);
			
		}
	}

}

// Set a really low weight for this module
// to make it run first

function hook_it_install() {
  db_update('system')
    ->fields(array('weight' => -1000))
    ->condition('name', 'drupalize_class', '=')
    ->execute();
}




// Test

class Cat extends HookIt {

	public function eat(){
		echo "YUM!<br>";
	}

	public function bark(&$x){
		$x[] = "då";
		echo "WOFF!<br>";
	}


	public function __construct(){
		$this->hookIt(array(
			"boot" => "eat,bark",
			"init" => "eat"
		));
	}
}


$cat = new Cat();
$a = array("hej");
cool_boot($a);
var_dump($a);

