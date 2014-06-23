<?php // no namespace

class HookIt {

  // A memory for how hooks a connected to methods
  private static $hookMem = array();

  // On instantiation instantiate
  // autoloader and help + connect to relevant hooks
  public function __construct(){
    include_once("autoloader.class.php");
    new hook_it\Autoloader();
    new hook_it\Help();
    $this->hookIt(array(
      "install" => "installer",
      "boot" => "emptyMethod"
    ));
  }

  // An empty method 
  // (we connect to boot to ensure the module keppes the
  //  value bootstrap = 1 in the system table in the DB
  protected function emptyMethod(){}

  // The installer sets a low weight for the module
  // to ensure that the class HookIt is available
  // to all other modules
  private function installer(){
    db_update('system')
      ->fields(array('weight' => -1000, 'bootstrap' => 1))
      ->condition('name', $this->getModuleName(), '=')
      ->execute();
  }

  // This is just an alias for hookIt (see below)
  protected function hook_it($hookSettings){
    $this->hookIt($hookSettings);
  }

  // This method is the entry point for connecting
  // methods to hooks
  protected function hookIt($hookSettings){
    $module = $this->getModuleName();
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

  // A "man-in-the-middle" method to make hookIt
  // work with protected methods
  public function __hookresolve__($method,$args){
    return call_user_func_array(array($this,$method),$args);
  }

  // Calculate the path to the current module
  // i.e. the module the class that defined $this is in
  // (works inside methods listening to hooks)
  protected function getModulePath() {
    $rc = new ReflectionClass(get_class($this));
    $dir = dirname($rc->getFileName());
    while($dir && !count(glob($dir."\/*\.module"))){
      $dir = explode("/",$dir);
      array_pop($dir);
      $dir = implode("/",$dir);
    }
    return $dir;
  }

  protected function getModuleName(){
    $path = explode("/",$this-> getModulePath());
    return array_pop($path);
  }


  // Register a connection between a method and a hook
  // in the $hookMem
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
        $params.= ($i ? ',' : '').
          (strpos("_alter",$hook) !== FALSE ? '&' : '').
          '$param'.$i. ' = null ';
      }
      // And a function for the hook
      eval(
        "function ".$module."_".$hook.'('.$params.'){'.
        '$argc = func_num_args();$params = array();'.
          'for ($i = 0; $i < $argc; $i++) {'.
          '$name = "param".$i;'.
          '$params[] = & $$name;}'.
        'return HookIt::hookLookup'.
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

  // Lookup a connection between a hook and a method
  // in the $hookMem
  public static function hookLookup($moduleName,$hookName,$args){

    $mem = self::$hookMem;

    // Don't attempt anything if not registrered properly
    if(!isset($mem[$moduleName]) || !isset($mem[$moduleName][$hookName])){
      return;
    }

    // Call registrered classes and methods
    $toReturn = null;
    foreach($mem[$moduleName][$hookName] as $method){
      
      if(!method_exists($method["obj"],$method["method"])){
        continue;
      }

      $r = call_user_func_array(
        array($method["obj"], "__hookresolve__"),
        array($method["method"], $args)
      );

      // Remember the last return value that is not null
      if($r!==null){ $toReturn = $r; }

    }

    return $toReturn;

  }

}