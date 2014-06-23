<?php namespace hook_it;

class Autoloader {

  public function __construct(){
    // Make sure we only build the autoloader once
    if(self::$autoloaderSet){
      return;
    }
    self::$autoloaderSet = true;
    // Go build it
    $this->buildAutoloader();
  }

  // Flag for it the autoloader has been built
  public static $autoloaderSet;
 
  // Calculate the path to the current module
  // static approach from debug_backtrace
  // (works for the autoloader)
  public static function getModulePath() {
    ob_start();
    var_dump(debug_backtrace());
    $result = explode(".module",ob_get_clean());
    $result = array_pop(explode('"',$result[0]));
    $result = explode("/",$result);
    array_pop($result);
    $result = implode("/",$result);
    return $result;
  }

  public static function getModuleName(){
    $path = explode("/",self::getModulePath());
    return array_pop($path);
  }

  // Build the autoloader if not build before
  private function buildAutoloader(){
    // Calculate the path to a class file
    // assuming it exists in a subfolder to the module
    // called classes
    spl_autoload_register(function ($class) {
      // Get the name of the class (without namespace)
      $class = array_pop(explode('\\',$class));
      // Get the module name
      $moduleName = Autoloader::getModuleName();
      // Calculate a file path to the class to include
      $file = Autoloader::getModulePath().'/classes/' .
        strtolower($class) . '.class.php';
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

}