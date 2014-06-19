hookIt
======

A Drupal module that let's you connect hooks to object methods.


The hookIt module.

Nodebite 2014, Thomas Frank

This module does nothing in itself.
It is a  helper module that simplifies listening
to hooks when you write class based code in other modules

Usage: Connect your methods to hooks by

1) Extending your class from HookIt:

   class myClass extends HookIt {...}

2) Telling hookIt which methods that should be connected
   to Drupal hooks in your constructor (or any method you
   call in your constructor):
   
   $this->hookIt(array(
     "hookName" => "methodName",  [OR]
     "hookName1, hookName2" => "methodName", [OR]
     "hookName" => "methodName1, methodName2"
   ));

Please Note: Visibility!
Methods you hook must be public or protected,
they can not be private!
