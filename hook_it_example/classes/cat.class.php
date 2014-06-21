<?php namespace hook_it_example;

class Cat extends \HookIt {
  
  public function talk() {
    drupal_set_message("Meow!");
  }

  public function eat() {
    drupal_set_message("Yum! Cat food!");
  }

  public function __construct(){
    $this->hookIt(array(
      "boot" => "talk",
      "init" => "eat"
    ));
  }
  
}