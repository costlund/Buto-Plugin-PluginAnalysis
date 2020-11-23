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
      wfPlugin::enable('theme/include');
      wfPlugin::enable('datatable/datatable_1_10_18');
      wfPlugin::enable('wf/table');
      wfPlugin::enable('image/element');
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
            $usage[] = array('name' => $item->get('name'), 'icon_path' => $item->get('icon_path'), 'row_click' => "PluginPluginAnalysis.plugin('". str_replace("/", '.', $item->get('name'))."')");
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
        $history[] = array('version' => $key, 'date' => $item->get('date'), 'title' => $item->get('title'), 'description' => $item->get('description'), 'webmaster' => $item->get('webmaster') );
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
  public function page_versions_update(){
    /**
     * 
     */
    $this->setPlugin();
    /**
     * 
     */
    $manifest = $this->update_manifest_versions(wfRequest::get('id'));
    /**
     * 
     */
    exit('Versions was updated!');
  }
  public function page_versions_update_all(){
    /**
     * Set plugins.
     */
    $this->setPlugins();
    /**
     * Update manifest.yml for each plugin.
     */
    $i = 0;
    foreach($this->plugins->get() as $k => $v){
      /**
       * Must have conflict.
       */
      if($this->plugins->get("$k/conflict")!='Yes'){
        continue;
      }
      /**
       * Must have clean repo.
       */
      if($this->plugins->get("$k/git/has")!='Yes'){
        continue;
      }
      /**
       * 
       */
      $i++;
      /**
       * 
       */
      $manifest = $this->update_manifest_versions($k);
      $this->git_run($k);
      wfHelp::yml_dump($k);
      if($i==10){
        exit("Update versions done ($i)!");
      }
    }
    /**
     * 
     */
    exit('Update versions done!');
  }
  private function git_run($plugins_key){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash($plugins_key));
    $git->add();
    $git->commit('Versions');
    return null;
  }
  private function update_manifest_versions($plugins_key){
    $manifest = new PluginWfYml(wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$plugins_key/name").'/manifest.yml');
    foreach($this->plugins->get("$plugins_key/manifest/plugin") as $key => $value) {
      $i = new PluginWfArray($value);
      $version = $this->plugins->get(str_replace('/', '.', $i->get('name')).'/manifest/version');
      $manifest->set("plugin/$key/name", $i->get('name'));
      $manifest->set("plugin/$key/version", $version);
    }
    $manifest->save();
    return $manifest;
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
          $version = '1.0.0';
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
      $data->set('manifest/history/1.0.0/date', date('Y-m-d'));
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
    if(!wfRequest::get('cc')){
      $this->setPlugins(true);
    }else{
      $this->setPlugins();
    }
    $this->plugin = new PluginWfArray($this->plugins->get($id));
    /**
     * public_folder_files
     */
    if($this->plugin->get('has_public_folder')){
      $files_right = array();
      if($this->plugin->get('has_public_folder_twin')){
        $files_right = $this->scan_dir(wfGlobals::getWebDir().'/plugin/'.$this->plugin->get('name'));
      }
      $files_left = $this->scan_dir(wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/public');
      /**
       * Set right if exist on both.
       */
      foreach($files_left as $k => $v){
        $files_left[$k]['left'] = true;
        if(isset($files_right[$k])){
          $files_left[$k]['right'] = true;
          $files_left[$k]['size_right'] = $files_right[$k]['size'];
        }
      }
      /**
       * Set right if exist on right.
       */
      foreach($files_right as $k => $v){
        if(!isset($files_left[$k])){
          $files_left[$k]['size_right'] = $files_right[$k]['size'];
          $files_left[$k]['right'] = true;
        }
      }
      /**
       * Set exist.
       */
      foreach($files_left as $k => $v){
        $i = new PluginWfArray($v);
        if($i->get('left') && $i->get('right')){
          $files_left[$k]['exist'] = 'both';
        }elseif($i->get('left')){
          $files_left[$k]['exist'] = 'left';
        }elseif($i->get('right')){
          $files_left[$k]['exist'] = 'right';
        }
      }
      /**
       * Set size_diff.
       */
      foreach($files_left as $k => $v){
        $i = new PluginWfArray($v);
        if($i->get('left') && $i->get('right')){
          if($i->get('size') == $i->get('size_right')){
            $files_left[$k]['size_diff'] = 'No';
          }else{
            $files_left[$k]['size_diff'] = 'Yes';
          }
        }else{
          $files_left[$k]['size_diff'] = '';
        }
      }
      /**
       * Set name.
       */
      foreach($files_left as $k => $v){
        $files_left[$k]['name'] = $k;
      }
      /**
       * Set data.
       */
      $this->plugin->set('public_folder_files', $files_left);
    }else{
      /**
       * Set data.
       */
      $this->plugin->set('public_folder_files', array());
    }
    /**
     * 
     */
    if($this->plugin->get('has_manifest')=='Yes'){
      $this->set_search_plugin($this->plugin->get('name'));
      if($this->plugin->get('manifest/plugin')){
        foreach ($this->plugin->get('manifest/plugin') as $k => $v) {
          $v['id_dot'] = str_replace('/', '.', $v['name']);
          $this->plugin->set("manifest/plugin/$k/find", 'M');
          $this->plugin->set("manifest/plugin/$k/icon_path", $this->plugins->get($v['id_dot']."/icon_path"));
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
        /**
         * Click on name in cell
         */
        $this->plugin->set("manifest/plugin/$key/name_click", "<a href=\"#\" onclick=\"PluginPluginAnalysis.plugin('".str_replace("/", '.', $item->get('name'))."')\">".$item->get('name')."</a>");
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
    /**
     * 
     */
    if(!$exist){
      $file = wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/README.md';
      $exist = wfFilesystem::fileExist($file);
    }
    /**
     * 
     */
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
  /**
   * Compare part one and two in versions like 1.2 or 1.2.3.
   * 1.2 -> 1.2 = true
   * 1.3 -> 1.2 = false
   * 1.2.0 -> 1.2.0 = true
   * 1.2.1 -> 1.2.0 = true
   * 1.3.0 -> 1.2.0 = false
   */
  private function version_compare($v1, $v2){
    wfPlugin::includeonce('string/array');
    $obj = new PluginStringArray();
    /**
     * 
     */
    $v1_a = ($obj->from_char(str_replace('.', ':', $v1), ':'));
    $v2_a = ($obj->from_char(str_replace('.', ':', $v2), ':'));
    $match = false;
    /**
     * Versions should have like 1.2 or 1.2.3 to be compared.
     */
    if(sizeof($v1_a)>=2 && sizeof($v2_a)>=2){
      if($v1_a[0]==$v2_a[0] && $v1_a[1]==$v2_a[1]){
        $match = true;
      }
    }else{
      if($v1 == $v2){
        $match = true;
      }
    }
    return $match;
  }
  public function setPlugins($cache = false){
    /**
     * Cache filename
     */
    $filename = wfGlobals::getAppDir().'/../buto_data/theme/[theme]/plugin_analysis_plugins.yml';
    /**
     * Cache get
     */
    if($cache && wfFilesystem::fileExist($filename)){
      $temp = new PluginWfYml($filename);
      $this->plugins = new PluginWfArray($temp->get());
      return null;
    }
    /**¨
     * ¨
     */
    $plugins_folder = wfGlobals::getAppDir().'/plugin';
    $plugin_array = array();
    if(wfRequest::get('theme')){
      wfPlugin::includeonce('theme/analysis');
      $theme_analysis = new PluginThemeAnalysis(array('secure' => false));
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
      $plugin->set($value_dot.'/git/active_branch', null);
      if($git->exist()){
        try {
          /**
           * 
           */
          $plugin->set($value_dot.'/git/status', $git->status());
          /**
           * 
           */
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
          /**
           * 
           */
          $plugin->set($value_dot.'/git/log_date_last', $git->log_date_last());
          /**
           * 
           */
          $plugin->set($value_dot.'/git/active_branch', $git->active_branch());
        } catch (Exception $exc) {
          $plugin->set($value_dot.'/git/has', 'Error ('.$exc->getMessage().')');
        }
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
       * Icon path
       */
      $plugin->set($value_dot.'/icon_path', '/plugin/'.$value.'/icon/icon.png');
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
      /**
       * 
       */
      $item = new PluginWfArray($value);
      /**
       * Set to null and later to a string with plugins (wf/array(1.2.0), wf/yml(1.2.2), ).
       */
      $this->plugins->set("$key/plugins", null);
      /**
       * conflict, plugins, id, url_id
       */
      $conflict = null;
      if($item->get('manifest/plugin')){
        $str = null;
        foreach ($item->get('manifest/plugin') as $key2 => $value2) {
          $plugin = new PluginWfArray($value2);
          /**
           * version_manifest
           */
          $version_manifest = $this->plugins->get(str_replace('/', '.', $plugin->get('name'))."/version_manifest");
          $star = null;
          if(!$this->version_compare($version_manifest, $plugin->get('version'))){
            $star = '*';
            $conflict = 'Yes';
          }
          $this->plugins->set("$key/manifest/plugin/$key2/version_manifest", $version_manifest);
          $str .= $plugin->get('name').'('.$plugin->get('version').$star.'), ';
        }
        $this->plugins->set("$key/plugins", $str);
      }
      $this->plugins->set("$key/conflict", $conflict);
      $this->plugins->set("$key/id", $key);
      $this->plugins->set("$key/url_id", str_replace('.', '_A_DOT_', $key));
    }
    //wfHelp::yml_dump($this->plugins->get(), true);
    /**
     * Cache save
     */
    $temp = new PluginWfYml($filename);
    $temp->yml = $this->plugins->get();
    $temp->save();
    /**
     * 
     */
    return null;
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
    $data->set('remote_get_url_origin', $git->remote_get_url_origin());
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
    $str = str_replace('_A_DOT_', "/", $str);
    $str = str_replace('.', "/", $str);
    return $str;
  }
}
