<?php namespace hook_it_example;

class Dog extends \HookIt {
  
  public function talk() {
    drupal_set_message("Woff!");
  }
  
  public function eat() {
    drupal_set_message("Yum! Dog food!");
  }
  
  public function __construct(){
    $this->hookIt(array(
      "boot" => "talk",
      "init" => "eat"
    ));
  }
  
}