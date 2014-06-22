<?php namespace hook_it;

class Help extends \HookIt {

  public function __construct(){
    $this->hookIt(array(
      "help" => "display"
    ));
  }

  // Display the help for this module
  protected function display($path){
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

}