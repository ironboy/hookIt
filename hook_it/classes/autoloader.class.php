<?php namespace hook_it;

class Autoloader extends \Hookit {

  public function __construct(){
    $this->buildAutoloader();
  }

  // Make sure the autoloader is only created once
  private static $autoloaderSet;

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
      $moduleName = \Hookit::getModuleName();
      // Calculate a file path to the class to include
      $file = \Hookit::getModulePath().'/classes/' .
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