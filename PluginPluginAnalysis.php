<?php
/**
 * Plugin to analyse plugins for a theme including plugins used of other plugins via theme settings file and plugin manifest file.
 */
class PluginPluginAnalysis{
  private $settings = null;
  private $plugins = null;
  private $plugin = null;
  private $plugin_search = array();
  function __construct($buto) {
    if($buto){
      /**¨
       * ¨Include.
       */
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      /**
       * Enable.
       */
      wfPlugin::enable('datatable/datatable_1_10_16');
      wfPlugin::enable('wf/table');
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
            $usage[] = array('name' => $item->get('name'), 'row_click' => "PluginPluginAnalysis.plugin('". str_replace("/", '.', $item->get('name'))."')");
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
    //wfHelp::yml_dump($this->plugin->get('manifest/links'));
    $element->setByTag($this->plugin->get());
    $element->setByTag(array('plugin' => $this->plugin->get('manifest/plugin')));
    $usage = $this->getUsage();
    $element->setByTag(array('usage' => $usage, 'has_usage' => sizeof($usage)));
    $element->setByTag(array('history' => $this->getHistory()));
    $element->setByTag($this->plugin->get('manifest'), 'manifest', true);
    $links = $this->getLinks();
    $element->setByTag(array('links' => $this->getLinks(), 'has_links' => sizeof($links)));
    wfDocument::renderElement($element->get());
    wfHelp::yml_dump($this->plugin->get());
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
  private function doPluginSearch($needle, $content){
    /**
     * Find plugin in content.
     */
    $pos1 = 0;
    $pos2 = 0;
    $length = strlen($content);
    for($i=0;$i<10;$i++){
      $pos1 = strpos($content, $needle, $pos1+1);
      $pos2 = strpos($content, ')', $pos1+1);
      if($pos1===false){
        break;
      }
      $plugin = substr($content, $pos1+strlen($needle), $pos2-$pos1-strlen($needle));
      $plugin = str_replace("'", '', $plugin);
      $plugin = str_replace('"', '', $plugin);
      $this->plugin_search[] = array($needle, $pos1, $pos2, $plugin);
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
      $trs[] = $tr->get();
    }
    $element->setByTag(array('trs' => $trs));
    $element->setByTag(wfRequest::getAll(), 'rs', true);
    wfDocument::renderElement($element->get());
    wfHelp::yml_dump($this->plugins, true);
  }
  private function setPlugin(){
    $key = wfRequest::get('id');
    $key = str_replace('_A_DOT_', '.', $key);
    $this->setPlugins();
    $this->plugin = new PluginWfArray($this->plugins->get($key));
    if($this->plugin->get('manifest/plugin')){
      foreach ($this->plugin->get('manifest/plugin') as $key => $value) {
        $item = new PluginWfArray($value);
        $this->plugin->set("manifest/plugin/$key/row_click", "PluginPluginAnalysis.plugin('". str_replace("/", '.', $item->get('name'))."')");
      }
    }
    return null;
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
  private function setPlugins(){
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
    foreach ($plugin_array as $key => $value) {
      $value_dot = str_replace('/', '.', $value);
      $plugin->set($value_dot.'/name', $value);
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
}
