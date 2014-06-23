<?php namespace hook_it_example;

class Help extends \hook_it\Help {

  public function __construct() {
    // We reuse the help class included
    // in hook_it
    parent::__construct();
  }
  
}