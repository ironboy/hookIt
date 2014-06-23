<?php namespace hook_it_example;

class Module extends \HookIt {

  public function __construct() {
    new Cat();
    new Dog();
    new Help();
  }
  
}