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
      "menu" => "config",
      "form" => "form",
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

  // Config settings
  protected function config(){

    $items = array();
    $title = "Hook it";
    $description = "Adds support for hooks in classes, ".
      "also autoloads and namespaces classes.";

    $items['admin/config/development/hook_it'] = array(
      'title' =>  $title,
      'description' => $description,
      'page callback' => 'drupal_get_form',
      'page arguments' => array('hook_it_form'),
      'access arguments' => array('administer hook it'),
    );

    $items['admin/config/development/hook_it/hook_it'] = array(
      'title' =>  $title,
      'description' => $description,
      'access arguments' => array('administer hook it'),
      'weight' => -10,
      'type' => MENU_DEFAULT_LOCAL_TASK,
    );

    return $items;
  }

  protected function form(){
    $form['system_status'] = array(
      '#type' => 'textfield',
      '#title' => t('Status'),
      '#description' => t('Enter the current system status.'),
      '#default_value' => "cool ",
      '#size' => 40,
      '#maxlength' => 255,
    );

    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save status'),
    );

    return $form;
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
  public static function getModulePath() {;
    ob_start();
    var_dump(debug_backtrace());
    $result = explode(".module",ob_get_clean());
    $result = array_pop(explode('"',$result[0]));
    $result = explode("/",$result);
    array_pop($result);
    $result = implode("/",$result);
    return $result;
  }

  // Get the module name of the current module
  public static function getModuleName(){
    $path = explode("/",self::getModulePath());
    $name = array_pop($path);
    echo($name.'<br>');
    return $name;
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