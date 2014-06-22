<?php

class HookIt {

  // A memory for how hooks a connected to methods
  private static $hookMem = array();

  // A flag for if we have created the autoloader
  private static $autoloaderSet;

  // On instantiation build the autoloader
  // and hook install to the installer
  public function __construct(){
    $this->buildAutoLoader();
    $this->hookIt(array(
      "install" => "installer",
      "help" => "helper",
      "boot" => "emptyMethod"
    ));
  }

  // An empty method 
  // (we connect to boot to ensure the module keppes the
  //  value bootstrap = 1 in the system table in the DB
  protected function emptyMethod(){}

  // Build the autoloader if not build before
  private function buildAutoloader(){

    if(self::$autoloaderSet){
      return;
    }

    self::$autoloaderSet = true;

    // Calculate the path to a class file
    // assuming it exists in a subfolder to the module
    // called classes
    spl_autoload_register(function ($class) {
      // Get the name of the class (without namespace)
      $class = array_pop(explode('\\',$class));
      // Get the module name
      ob_start();
      var_dump(debug_backtrace());
      $result = explode(".module",ob_get_clean());
      $result = array_pop(explode('"',$result[0]));
      $result = explode("/",$result);
      array_pop($result);
      $result = implode("/",$result);
      $moduleName = array_pop(explode("/",$result));
      // Calculate a file path to the class to include
      $file = $result.'/classes/' . strtolower($class) . '.class.php';
      // If the file exists then include it with a correct namespace
      if(file_exists($file)){
        // Check that the file has a correct namespace
        // (equal to the module name else try to rewrite
        // the file with a module name)
        $data = file_get_contents($file);
        $classphp = explode("<?php",$data);
        $classphp[1] = explode("\n",$classphp[1]);
        $classphp[1][0] = " namespace $moduleName;";
        $classphp[1] = implode("\n",$classphp[1]);
        $classphp = implode("<?php",$classphp);
        if($classphp != $data){
          file_put_contents($file,$classphp);
        }
        // Include the file
        include ($file);
      }
    });
  }

  // The installer sets a low weight for the module
  // to ensure that the class HookIt is available
  // to all other modules
  private function installer(){
    db_update('system')
      ->fields(array('weight' => -1000, 'bootstrap' => 1))
      ->condition('name', $this->getModuleName(), '=')
      ->execute();
  }

  // Display the help for this module
  protected function helper($path){
    if ($path != 'admin/help#'.$this->getModuleName()) { return; }
    drupal_add_css("//fonts.googleapis.com/css?family=PT+Mono","external");
    drupal_add_css("//maxcdn.bootstrapcdn.com/font-awesome/4.1.0".
      "/css/font-awesome.min.css","external");
    drupal_add_css(
      ".hookithelp {".
      "  font-family:'PT Mono';".
      "  font-size:16px;".
      "  line-height:135%;".
      "  color:#eee;".
      "  border:10px solid #0073ba;".
      "  padding: 50px;".
      "  background:#014a76;".
      "  max-width:580px;".
      "  margin:0 auto 30px;".
      "}".
      ".hookithelp .fa {".
      "  margin-right:30px;".
      "  margin-bottom:30px;".
      "  font-size:120px;".
      "  display:block;".
      "  opacity: .6;".
      "  float:left;".
      "}",
      "inline"
    );
    $path = $this->getModulePath()."/".$this->getModuleName().'.info';
    if(!file_exists($path)){ return; }
    $info = t(file_get_contents($path));
    $info = "module ".str_replace("=",":",$info);
    $info = str_replace("\n"," - ",$info);
    $info = str_replace(" - description","\ndescription",$info);
    $info = str_replace(" - version","\n\nversion",$info);
    $path = $this->getModulePath()."/help.txt";
    if(!file_exists($path)){ return; }
    $help = $info."\n\n".t(file_get_contents($path));
    $help = str_replace('<','&lt;',$help);
    $help = str_replace('>','&gt;',$help);
    $help = "<i class=\"fa fa-drupal\"></i>".$help;
    $help = "<pre class=\"hookithelp\">$help</pre>";
    return $help;
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

  // Calaculate the path to the current module
  public function getModulePath() {
    $rc = new ReflectionClass(get_class($this));
    $dir = dirname($rc->getFileName());
    while($dir && !count(glob($dir."\/*\.module"))){
      $dir = explode("/",$dir);
      array_pop($dir);
      $dir = implode("/",$dir);
    }
    return $dir;
  }

  // Get the module name of the current module
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