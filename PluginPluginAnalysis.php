<?php
/**
 * Plugin to analyse plugins for a theme including plugins used of other plugins via theme settings file and plugin manifest file.
 */
class PluginPluginAnalysis{
  private $settings = null;
  public $plugins = null;
  private $plugin = null;
  private $plugin_search = array();
  function __construct($buto = false) {
    if($buto){
      /**¨
       * ¨Include.
       */
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('git/kbjr');
      /**
       * Enable.
       */
      wfPlugin::enable('datatable/datatable_1_10_16');
      wfPlugin::enable('wf/table');
      wfPlugin::enable('twitter/bootstrap335v');
      wfPlugin::enable('wf/ajax');
      wfPlugin::enable('wf/bootstrapjs');
      wfPlugin::enable('wf/callbackjson');
      wfPlugin::enable('wf/dom');
      wfPlugin::enable('wf/embed');
      /**
       * Only webmaster.
       */
      if(!wfUser::hasRole('webmaster')){
        exit('Role issue says PluginPluginAnalysis.');
      }
      /**
       * Layout path.
       */
      wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/plugin/analysis/layout');
      /**
       * Settings.
       */
      $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
    }
  }
  /**
   * Get all theme in array.
   * @return array
   */
  private function getTheme(){
    $theme = array();
    $path1 = wfGlobals::getAppDir().'/theme';
    $dir1 = wfFilesystem::getScandir($path1);
    foreach ($dir1 as $key1 => $value1) {
      $path2 = wfGlobals::getAppDir().'/theme/'.$value1;
      if(!is_file($path2)){
        $dir2 = wfFilesystem::getScandir($path2);
        foreach ($dir2 as $key2 => $value2) {
          $path3 = wfGlobals::getAppDir().'/theme/'.$value1.'/'.$value2;
          if(!is_file($path3)){
            $theme[] = $value1.'/'.$value2;
          }
        }
        
      }
    }
    return $theme;
  }
  public function page_theme_analys(){
    wfPlugin::includeonce('wf/yml');
    $page = new PluginWfYml(__DIR__.'/page/theme_analys.yml');
    
    
    $theme = $this->getTheme();
    $option = array();
    foreach ($theme as $key => $value) {
      $option[] = wfDocument::createHtmlElement('option', $value, array('value' => $value));
    }
    //wfHelp::dump($option);
    
    $page->setByTag(array('option' => $option));
    
    wfDocument::renderElement($page->get());
  }
  public function page_start(){
    wfPlugin::includeonce('wf/yml');
    $page = new PluginWfYml(__DIR__.'/page/start.yml');
    /**
     * Insert admin layout from theme.
     */
    $page = wfDocument::insertAdminLayout($this->settings, 1, $page);
    $json = json_encode(array('class' => wfGlobals::get('class')));
    $page->setByTag(array('json' => 'var app = '.$json));
    /**
     * Insert admin layout from theme.
     */
    wfDocument::mergeLayout($page->get());
  }
  private function getUsage(){
    $usage = array();
    foreach ($this->plugins->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('manifest/plugin')){
        foreach ($item->get('manifest/plugin') as $key2 => $value2) {
          $item2 = new PluginWfArray($value2);
          if($this->plugin->get('name')==$item2->get('name')){
            $usage[] = array('name' => $item->get('name'), 'icon_element' => $item->get('icon_element'), 'row_click' => "PluginPluginAnalysis.plugin('". str_replace("/", '.', $item->get('name'))."')");
          }
        }
      }
    }
    return $usage;
  }
  private function getHistory(){
    $history = array();
    if($this->plugin->get('manifest/history')){
      foreach ($this->plugin->get('manifest/history') as $key => $value) {
        $item = new PluginWfArray($value);
        $history[] = array('version' => $key, 'date' => $item->get('date'), 'description' => $item->get('description'));
      }
    }
    return $history;
  }
  private function getLinks(){
    $links = array();
    if($this->plugin->get('manifest/links')){
      foreach ($this->plugin->get('manifest/links') as $key => $value) {
        $links[] = array('name' => $key, 'url' => $value, 'row_click' => "window.open('$value')");
      }
    }
    return $links;
  }
  public function page_plugin(){
    $element = new PluginWfYml(__DIR__.'/element/plugin.yml');
    $this->setPlugin();
    $element->setByTag($this->plugin->get());
    $element->setByTag(array('plugin' => $this->plugin->get('manifest/plugin')));
    /**
     * Webmaster element.
     */
    wfPlugin::enable($this->plugin->get('name'));
    if(!$this->plugin->get('manifest/webmaster/element')){
      $this->plugin->set('manifest/webmaster/element', null);
    }
    $element->setByTag($this->plugin->get('manifest/webmaster'), 'webmaster');
    /**
     * 
     */
    $element->setByTag($this->plugin->get('git'), 'git');
    $usage = $this->getUsage();
    $element->setByTag(array('usage' => $usage, 'has_usage' => sizeof($usage)));
    $element->setByTag(array('history' => $this->getHistory()));
    $element->setByTag($this->plugin->get('manifest'), 'manifest', true);
    $links = $this->getLinks();
    $element->setByTag(array('links' => $this->getLinks(), 'has_links' => sizeof($links)));
    wfDocument::renderElement($element->get());
  }
  public function page_js_include_method(){
    $this->setPlugin();
    $contents = file_get_contents(__DIR__.'/data/js_include_method.php');
    $contents = str_replace("PluginXxxYyy.js", $this->plugin->get('js_name'), $contents);
    $element = array();
    $element[] = wfDocument::createHtmlElement('pre', $contents);
    wfDocument::renderElement($element);
  }
  public function page_plugin_create(){
    $element = new PluginWfYml(__DIR__.'/element/plugin_create.yml');
    wfDocument::renderElement($element->get());
  }
  public function page_plugin_create_run(){
    $error = new PluginWfArray(array('success' => false, 'error' => null));
    $name = wfRequest::get('name');
    /**
     * Check name.
     */
    if(!$error->get('error')){
      wfPlugin::includeonce('string/array');
      $plugin = new PluginStringArray();
      $array = $plugin->from_slash($name);
      if(sizeof($array)!=2){
        $error->set('error', 'One slash is required.');
      }
    }
    /**
     * Check exist.
     */
    if(!$error->get('error')){
      $path = wfGlobals::getAppDir()."/plugin/$name";
      if(wfFilesystem::fileExist($path)){
        $error->set('error', 'Folder already exist.');
      }
    }
    /**
     * Create plugin.
     */
    if(!$error->get('error')){
      $contents = file_get_contents(__DIR__.'/data/PluginXxxYyy.php');
      $contents = str_replace("PluginXxxYyy", "Plugin".wfPlugin::to_camel_case($name), $contents);
      $filename = wfGlobals::getAppDir().'/plugin/'.$name.'/Plugin'.wfPlugin::to_camel_case($name).'.php';
      wfFilesystem::createFile($filename, $contents);
    }
    /**
     * 
     */
    if(!$error->get('error')){
      $error->set('success', true);
    }
    /**
     * 
     */
    exit(json_encode($error->get()));
  }
  public function page_public_create(){
    /**
     * 
     */
    $this->setPlugin();
    if($this->plugin->get('has_public_folder_twin') && !wfRequest::get('update')){
      exit('Has already public folder.');
    }
    /**
     * 
     */
    $id = wfRequest::get('id');
    $plugin_name = str_replace('_A_DOT_', "/", $id);
    /**
     * Create copy array.
     */
    $copy = array();
    foreach ($this->plugin->get('files') as $key => $value) {
      if(substr($key, 0, 8) != '/public/'){
        continue;
      }
      $copy[] = array('from' => wfGlobals::getAppDir().'/plugin/'.$plugin_name.$key, 'to' => wfGlobals::getWebDir().'/plugin/'.$plugin_name.substr($key, 7));
    }
    /**
     * Copy files.
     */
    foreach ($copy as $value){
      wfFilesystem::copyFile($value['from'], $value['to']);
    }
    /**
     * 
     */
    exit(sizeof($copy)." files was copied to /$plugin_name!");    
  }
  public function page_js_create(){
    /**
     * 
     */
    $this->setPlugin();
    if($this->plugin->get('has_js')){
      exit('Has already js.');
    }
    /**
     * 
     */
    $id = wfRequest::get('id');
    $plugin_name = str_replace('_A_DOT_', "/", $id);
    $contents = file_get_contents(__DIR__.'/data/PluginXxxYyy.js');
    $contents = str_replace("PluginXxxYyy", "Plugin".wfPlugin::to_camel_case($plugin_name), $contents);
    $filename = wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/public/'.$this->plugin->get('js_name');
    $filename_web = wfGlobals::getWebDir().'/plugin/'.$plugin_name.'/'.$this->plugin->get('js_name');
    wfFilesystem::createFile($filename, $contents);
    wfFilesystem::createFile($filename_web, $contents);
    exit("File $filename and $filename_web was created !");
  }
  public function page_readme_create(){
    /**
     * 
     */
    $this->setPlugin();
    if($this->plugin->get('has_readme')=='Yes'){
      exit('Has already readme.');
    }
    /**
     * 
     */
    $id = wfRequest::get('id');
    $plugin_name = str_replace('_A_DOT_', "/", $id);
    $contents = file_get_contents(__DIR__.'/data/README.md');
    $contents = str_replace("# Buto-Plugin-_", "# Buto-Plugin-".wfPlugin::to_camel_case($plugin_name), $contents);
    $filename = wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/README.md';
    file_put_contents($filename, $contents);
    exit("File $filename was created!");
  }
  public function page_manifest_create(){
    /**
     * 
     */
    $this->setPlugin();
    if($this->plugin->get('manifest')){
      exit('has manifest...');
    }else{
      /**
       * 
       */
      $id = wfRequest::get('id');
      $plugin_name = str_replace('_A_DOT_', "/", $id);
      $this->set_search_plugin($plugin_name);
      $temp = array();
      foreach ($this->plugin_search as $key => $value) {
        $version = $this->plugins->get(str_replace("/", '.', $value[3]).'/version_manifest');
        if(is_null($version)){
          $version = '1.0';
        }
        $temp[$value[3]] = array('name' => $value[3], 'version' => $version);
      }
      $plugin = array();
      foreach ($temp as $key => $value) {
        $plugin[] = $value;
      }
      /**
       * 
       */
      $data = new PluginWfYml(__DIR__.'/data/data.yml');
      $data->set('manifest/plugin', $plugin);
      $data->set('manifest/history/1.0/date', date('Y-m-d'));
      /**
       * Create manifest.
       */
      $filename = wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/manifest.yml';
      if(!wfFilesystem::fileExist($filename)){
        wfHelp::yml_dump($filename);
        file_put_contents($filename, wfHelp::getYmlDump($data->get('manifest')));
      }
    }
  }
  /**
   * Set global param $this->search_plugin.
   * @param string $plugin_name xx/yy
   */
  private function set_search_plugin($plugin_name){
    /**
     * Get content.
     */
    $filename = wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/Plugin'.wfPlugin::to_camel_case($plugin_name).'.php';
    if(!wfFilesystem::fileExist($filename)){
      exit("$filename does not exist.");
    }
    $content = file_get_contents($filename);
    /**
     * Search for plugins.
     */
    $this->doPluginSearch('wfPlugin::includeonce(', $content);
    $this->doPluginSearch('wfPlugin::enable(', $content);
    $this->doPluginSearch('wfDocument::createWidget(', $content);
  }
  private function doPluginSearch($needle, $content){
    /**
     * Find (max 1000) plugins in content.
     */
    $pos1 = 0;
    $pos2 = 0;
    $length = strlen($content);
    for($i=0;$i<1000;$i++){
      $pos1 = strpos($content, $needle, $pos1+1);
      $pos2 = strpos($content, ')', $pos1+1);
      if($pos1===false){
        break;
      }
      $plugin = substr($content, $pos1+strlen($needle), $pos2-$pos1-strlen($needle));
      $plugin = str_replace("'", '', $plugin);
      $plugin = str_replace('"', '', $plugin);
      /**
       * Fix if wfDocument::createWidget(.
       */
      if(strstr($plugin, ',')){
        $plugin = substr($plugin, 0, strpos($plugin, ','));
      }
      /**
       *
       */
      if(!strstr($plugin, '$')){
        $this->plugin_search[] = array($needle, $pos1, $pos2, $plugin);
      }
    }
  }
  public function page_analys(){
    $this->setPlugins();
    $element = new PluginWfYml('/plugin/plugin/analysis/element/table.yml');
    $trs = array();
    foreach ($this->plugins->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $tr = new PluginWfYml('/plugin/plugin/analysis/element/table_tr.yml');
      $tr->setByTag($item->get());
      $tr->setByTag($item->get('git'), 'git');
      $trs[] = $tr->get();
    }
    $element->setByTag(array('trs' => $trs));
    $element->setByTag(wfRequest::getAll(), 'rs', true);
    wfDocument::renderElement($element->get());
  }
  private function setPlugin(){
    $id = wfRequest::get('id');
    $id = str_replace('_A_DOT_', '.', $id);
    $this->setPlugins();
    $this->plugin = new PluginWfArray($this->plugins->get($id));
    /**
     * 
     */
    if($this->plugin->get('has_manifest')=='Yes'){
      $this->set_search_plugin($this->plugin->get('name'));
      if($this->plugin->get('manifest/plugin')){
        foreach ($this->plugin->get('manifest/plugin') as $k => $v) {
          $v['id_dot'] = str_replace('/', '.', $v['name']);
          $this->plugin->set("manifest/plugin/$k/find", 'M');
          $this->plugin->set("manifest/plugin/$k/icon_element", $this->plugins->get($v['id_dot']."/icon_element"));
        }
        /**
         * Check if plugin in code exist in manifest.
         */
        foreach ($this->plugin->get('manifest/plugin') as $k => $v) {
          foreach ($this->plugin_search as $v2) {
            if($v2[3]== $this->plugin->get("manifest/plugin/$k/name")){
              $this->plugin->set("manifest/plugin/$k/find", 'MC');
              break;
            }
          }
        }
        /**
         * Check if plugin in manifest exist in code.
         */
        foreach ($this->plugin_search as $v) {
          $manifest = 'No';
          foreach ($this->plugin->get('manifest/plugin') as $k2 => $v2){
            if($v[3]== $v2['name']){
              $manifest = null;
              break;
            }
          }
          if($manifest == 'No'){
            $this->plugin->set("manifest/plugin/", array('name' => $v[3], 'version' => null, 'version_manifest' => null, 'find' => 'C'));
          }
        }
      }
    }
    if($this->plugin->get('manifest/plugin')){
      foreach ($this->plugin->get('manifest/plugin') as $key => $value) {
        $item = new PluginWfArray($value);
        $this->plugin->set("manifest/plugin/$key/row_click", "PluginPluginAnalysis.plugin('". str_replace("/", '.', $item->get('name'))."')");
      }
    }
    $theme_usage_temp = $this->getThemesUsingPlugin($id);
    $theme_usage = array();
    foreach ($theme_usage_temp as $key => $value) {
      $theme_usage[] = $value;
    }
    $this->plugin->set('theme_usage', $theme_usage);
    /**
     * Get README.md content.
     */
    $readme = null;
    $file = wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/readme.md';
    $exist = wfFilesystem::fileExist($file);
    if($exist){
      $readme = file_get_contents($file);
      wfPlugin::includeonce('readme/parser');
      $parser = new PluginReadmeParser();
      $readme = $parser->parse_text($readme);
      $this->plugin->set('readme', $readme);
      $this->plugin->set('has_readme', 'Yes');
    }else{
      $this->plugin->set('readme', $readme);
      $this->plugin->set('has_readme', 'No');
    }
    /**
     * Get Js.
     */
    $js = null;
    $this->plugin->set('js_name', 'Plugin'.wfPlugin::to_camel_case($this->plugin->get('name')).'.js');
    $file = wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/public/'.$this->plugin->get('js_name');
    $exist = wfFilesystem::fileExist($file);
    if($exist){
      $this->plugin->set('has_js', true);
      $this->plugin->set('has_js_text', 'Yes');
    }else{
      $this->plugin->set('has_js', false);
      $this->plugin->set('has_js_text', 'No');
    }
    /**
     * 
     */
    return null;
  }
  /**
   * Get themes used by a plugin.
   * @param string $plugin wf/example or wf.example
   * @return array
   */
  private function getThemesUsingPlugin($plugin){
    $plugin = str_replace('/', '.', $plugin);
    $theme = $this->getTheme();
    wfPlugin::includeonce('theme/analysis');
    $theme_usage = array();
    foreach ($theme as $key => $value) {
      $obj = new PluginThemeAnalysis(true);
      $obj->setData($value);
      if($obj->data->get($plugin)){
        $theme_usage[$value] = array('name' => $value, 'plugin' => $obj->data->get("$plugin/plugin"), 'plugin_module' => $obj->data->get("$plugin/plugin_module"), 'event' => $obj->data->get("$plugin/event"));
      }
    }
    return $theme_usage;
  }
  private function scan_dir($dir){
    if(!wfFilesystem::fileExist($dir)){
      exit("Dir $dir does not exist.");
    }
    $startfolder=$dir;
    $files=array();
    foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $startfolder, RecursiveDirectoryIterator::KEY_AS_PATHNAME ), RecursiveIteratorIterator::CHILD_FIRST ) as $file => $info ) {
        if( $info->isFile() && $info->isReadable() ){
            $files[substr($info->getPathname(), strlen($dir))]=array('size'=> filesize($info->getPathname()) );
        }
    }
    return $files;
  }
  /**
   * Get all plugins in array.
   */
  private function getPluginArray(){
    $dir = wfGlobals::getAppDir().'/plugin';
    $data = wfFilesystem::getScandir($dir);
    $plugin = array();
    foreach ($data as $key => $value) {
      if(is_dir($dir.'/'.$value)){
        $dir2 = $dir.'/'.$value;
        $data2 = wfFilesystem::getScandir($dir2);
        foreach ($data2 as $key2 => $value2) {
          if(is_dir($dir2.'/'.$value2)){
            $plugin[] = $value.'/'.$value2;
          }
        }
      }
    }
    return $plugin;
  }
  public function setPlugins(){
    $plugins_folder = wfGlobals::getAppDir().'/plugin';
    $plugin_array = array();
    if(wfRequest::get('theme')){
      wfPlugin::includeonce('theme/analysis');
      $theme_analysis = new PluginThemeAnalysis(true);
      $theme_analysis->setData(wfRequest::get('theme'));
      foreach ($theme_analysis->data->get() as $key => $value) {
        $plugin_array[] = $value['name'];
      }
    }else{
      $plugin_array = $this->getPluginArray();
    }
    $plugin = new PluginWfArray();
    wfPlugin::includeonce('git/kbjr');
    $git = new PluginGitKbjr();
    foreach ($plugin_array as $key => $value) {
      $value_dot = str_replace('/', '.', $value);
      $plugin->set($value_dot.'/name', $value);
      /**
       * Git
       */
      $git->set_repo($value);
      $plugin->set($value_dot.'/git/status', null);
      $plugin->set($value_dot.'/git/has', null);
      $plugin->set($value_dot.'/git/log_date_last', null);
      if($git->exist()){
        $plugin->set($value_dot.'/git/status', $git->status());
        if(strstr($git->status(), 'Your branch is behind')){
          $plugin->set($value_dot.'/git/has', 'Yes (behind)');
        }elseif(strstr($git->status(), 'Your branch is ahead of')){
          $plugin->set($value_dot.'/git/has', 'Yes (ahead)');
        }elseif(strstr($git->status(), 'but the upstream is gone')){
          $plugin->set($value_dot.'/git/has', 'Yes (upstream)');
        }elseif(strstr($git->status(), 'nothing to commit, working tree clean')){
          $plugin->set($value_dot.'/git/has', 'Yes');
        }else{
          $plugin->set($value_dot.'/git/has', 'Yes (changes)');
        }
        $plugin->set($value_dot.'/git/log_date_last', $git->log_date_last());
      }
      /**
       * 
       */
      $has_public_folder = false;
      $has_public_folder_text = null;
      $has_public_folder_twin = null;
      $has_public_folder_twin_text = null;
      $public_folder_match = null;
      $public_folder_match_text = null;
      $public_folder = $plugins_folder.'/'.$value.'/public';
      $public_folder_twin = wfGlobals::getWebDir().'/plugin/'.$value;
      if(is_dir($public_folder)){
        $has_public_folder = true;
        $has_public_folder_text = 'Yes';
        $has_public_folder_twin = is_dir($public_folder_twin);
        if($has_public_folder_twin){
          $has_public_folder_twin_text = 'Yes';
          if($this->scan_dir($public_folder)==$this->scan_dir($public_folder_twin)){
            $public_folder_match = true;
            $public_folder_match_text = 'Yes';
          }else{
            $public_folder_match = false;
            $public_folder_match_text = 'No';
          }
        }else{
          $has_public_folder_twin_text = 'No';
        }
      }
      $plugin->set($value_dot.'/has_public_folder', $has_public_folder);
      if($has_public_folder && (!$has_public_folder_twin || !$public_folder_match)){
        $has_public_folder_text .= '*';
      }
      $plugin->set($value_dot.'/has_public_folder_text', $has_public_folder_text);
      $plugin->set($value_dot.'/has_public_folder_twin', $has_public_folder_twin);
      $plugin->set($value_dot.'/has_public_folder_twin_text', $has_public_folder_twin_text);
      $plugin->set($value_dot.'/public_folder_match', $public_folder_match);
      $plugin->set($value_dot.'/public_folder_match_text', $public_folder_match_text);
      $plugin->set($value_dot.'/files', $this->scan_dir($plugins_folder.'/'.$value));
      $plugin->set($value_dot.'/files_count', sizeof($plugin->get($value_dot.'/files')));
      /**
       * Icon
       */
      $plugin->set($value_dot.'/icon_element', null);
      $filename = wfGlobals::getWebDir().'/plugin/'.$value.'/icon/icon.png';
      if(wfFilesystem::fileExist($filename)){
        $str = '<img src="/plugin/'.$value.'/icon/icon.png" style="width:30px">';
        $plugin->set($value_dot.'/icon_element', $str);
      }
    }
    $this->plugins = new PluginWfArray($plugin->get());
    /**
     * Manifest from theme plugin.
     */
    foreach ($this->plugins->get() as $key => $value) {
      $this->setManifest($key, $value);
    }
    
    /**
     * Manage data...
     */
    foreach ($this->plugins->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $this->plugins->set("$key/plugins", null);
      $conflict = null;
      if($item->get('manifest/plugin')){
        $str = null;
        foreach ($item->get('manifest/plugin') as $key2 => $value2) {
          $plugin = new PluginWfArray($value2);
          $version = $this->plugins->get(str_replace('/', '.', $plugin->get('name'))."/version_manifest");
          $star = null;
          if($version != $plugin->get('version')){
            $star = '*';
            $conflict = 'Yes';
          }
          $this->plugins->set("$key/manifest/plugin/$key2/version_manifest", $version);
          $str .= $plugin->get('name').'('.$plugin->get('version').$star.'), ';
        }
        $this->plugins->set("$key/plugins", $str);
      }
      $this->plugins->set("$key/conflict", $conflict);
      $this->plugins->set("$key/id", $key);
      $this->plugins->set("$key/url_id", str_replace('.', '_A_DOT_', $key));
    }
  }
  private function setManifest($key, $value){
    $item = new PluginWfArray($value);
    $filename = wfGlobals::getAppDir().'/plugin/'.$item->get('name').'/manifest.yml';
    if(wfFilesystem::fileExist($filename)){
      $manifest = new PluginWfYml($filename);
      $this->plugins->set("$key/has_manifest", 'Yes');
      $this->plugins->set("$key/manifest", $manifest->get());
      $this->plugins->set("$key/version_manifest", $manifest->get('version'));
      if(is_array($manifest->get('plugin'))){
        foreach ($manifest->get('plugin') as $key2 => $value2) {
          $item = new PluginWfArray($value2);
          $this->plugins->set(str_replace('/', '.', $value2['name']).'/name', $value2['name']);
        }
      }
    }else{
      $this->plugins->set("$key/has_manifest", 'No');
      $this->plugins->set("$key/manifest", null);
      $this->plugins->set("$key/version_manifest", null);
    }
  }
  public function page_git(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $data = new PluginWfArray();
    $data->set('status', $git->status());
    $data->set('log', $git->log());
    $element = new PluginWfYml(__DIR__.'/element/git.yml');
    $element->setByTag($data->get());
    $element->setByTag(wfRequest::getAll());
    wfDocument::renderElement($element->get());
  }
  public function page_git_add(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->add();
    exit('Add...');
  }
  public function page_git_push(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->push();
    exit('Push...');
  }
  public function page_git_pull(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->pull();
    exit('Pull...');
  }
  public function page_git_fetch(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->fetch();
    exit('Fetch...');
  }
  public function page_git_commit(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->commit(wfRequest::get('message'));
    exit('Commit...');
  }
  public function page_git_diff(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $element = wfDocument::createHtmlElement('pre', $git->diff(wfRequest::get('filename')));
    wfDocument::renderElement(array($element));
  }
  private function replace_a_dot_to_slash($str){
    return str_replace('_A_DOT_', "/", $str);
  }
}
