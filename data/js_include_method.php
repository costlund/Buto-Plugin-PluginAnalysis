public static function widget_include(){
  wfPlugin::enable('include/js');
  wfDocument::renderElement(array(wfDocument::createWidget('include/js', 'include', array('src' => '/plugin/js/date_diff/PluginXxxYyy.js'))));
}