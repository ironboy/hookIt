<?php
/*
	The HookIt module.

	Nodebite 2014, Thomas Frank

	This module does nothing in itself.
	It is a  helper module that simplifies listening
	to hooks when you write class based code in other modules

	Usage: Connect your methods to hooks by

	1) Extending your class from HookIt:

	   class myClass extends HookIt {...}
	
	2) Telling HookIt which methods that should be connected
	   to Drupal hooks in your constructor (or any method you
	   call in your constructor):
	   
	   $this->hookIt(array(
			 "hookName" => "methodName",  [OR]
			 "hookName1, hookName2" => "methodName", [OR]
			 "hookName" => "methodName1, methodName2"
	   ));

*/

class HookIt {

	private static $hookMem = array();

	protected function hook_it($hookSettings){
		// an alias for hookIt (see below)
		$this->hookIt($hookSettings);
	}

	protected function hookIt($hookSettings){
		// connect the methods to hooks
		$module = $this->currentModule();
		foreach ($hookSettings as $hooks => $methods){
			$methods = explode(",",str_replace(" ","",$methods));
			$hooks = explode(",",str_replace(" ","",$hooks));
			foreach($hooks as $hook){
				foreach($methods as $method){
					HookIt::regHook($module,$hook,$method,$this);
				}
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

		// Avoid duplicate hook connections
		foreach($mem[$module][$hook] as $con){
			if($con["obj"] === $obj && $con["method"] == $method){
				return;
			}
		}

		// Add method to memory
		$mem[$module][$hook][] = array(
			"obj" => $obj,
			"method" => $method
		);

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

			// Using a "man-in-the-middle" method __hookresolve__
			// we can hook protected methods (not only public ones)
			call_user_func_array(
				array($method["obj"], "__hookresolve__"),
				array($method["method"], $args)
			);
			
		}

	}

	// Resolve calls to methods that are hooked
	public function __hookresolve__($method,$args){
		call_user_func_array(array($this,$method),$args);
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
