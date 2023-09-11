<?php
class PluginPluginAnalysis{
  private $settings = null;
  public $plugins = null;
  private $plugin = null;
  private $plugin_search = array();
  private $history = array();
  private $limit = 999; //Set to lower when developing if needed.
  function __construct($buto = false) {
    /**
     * Time limit.
     */
    set_time_limit(60*5);
    /**
     * 
     */
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
      wfGlobals::setSys('layout_path', '/plugin/plugin/analysis/layout');
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
            $usage[] = array('name' => $item->get('name'), 'icon_path' => $item->get('icon_path'), 'row_click' => "PluginPluginAnalysis.plugin('". wfPhpfunc::str_replace("/", '.', $item->get('name'))."')");
          }
        }
      }
    }
    return $usage;
  }
  private function getHistory($version = null){
    if(!$version){
      $history = array();
      if($this->plugin->get('manifest/history')){
        foreach ($this->plugin->get('manifest/history') as $key => $value) {
          $item = new PluginWfArray($value);
          $history[] = array('version' => $key, 'date' => $item->get('date'), 'title' => $item->get('title'), 'description' => $item->get('description'), 'webmaster' => $item->get('webmaster'), 'row_click' => "PluginPluginAnalysis.history_form('".wfRequest::get('id')."', '$key')" );
        }
      }
      return $history;
    }else{
      $history = new PluginWfArray();
      if($this->plugin->get('manifest/history')){
        foreach ($this->plugin->get('manifest/history') as $key => $value) {
          if($key==$version){
            $history = new PluginWfArray($value);
            $history->set('version', $key);
            break;
          }
        }
      }
      return $history;
    }
  }
  public function page_history_form(){
    wfPlugin::enable('form/form_v1');
    $element = new PluginWfYml(__DIR__.'/element/history_form.yml');
    $element->setByTag(array('method' => 'render'));
    wfDocument::renderElement($element->get());
  }
  public function form_history_render($form){
    $form = new PluginWfArray($form);
    $this->setPlugin();
    if(wfRequest::get('version')){
      $history = $this->getHistory(wfRequest::get('version'));
    }else{
      $history = new PluginWfArray();
      $history->set('date', date('Y-m-d'));
    }
    $history->set('id', wfRequest::get('id'));
    $version1 = $this->version_upgrade($this->plugin->get('manifest/version'));
    $version2 = $this->version_upgrade($this->plugin->get('manifest/version'), true);
    $option = array();
    $option[$version1] = $version1;
    $option[$version2] = $version2;
    $form->setByTag(array('option_new_version' => $option));
    $form->setByTag($history->get(), 'rs', true);
    return $form->get();
  }
  public function form_i18n_render($form){
    $form = new PluginWfArray($form);
    $form->setByTag(array('id' => wfRequest::get('id')));
    return $form->get();
  }
  public function page_history_capture(){
    wfPlugin::enable('form/form_v1');
    $element = new PluginWfYml(__DIR__.'/element/history_form.yml');
    $element->setByTag(array('method' => 'capture'));
    wfDocument::renderElement($element->get());
  }
  public function page_i18n_capture(){
    wfPlugin::enable('form/form_v1');
    $element = new PluginWfYml(__DIR__.'/element/page_i18n_form.yml');
    $element->setByTag(array('method' => 'capture'));
    wfDocument::renderElement($element->get());
  }
  public function form_i18n_capture(){
    wfPlugin::includeonce('string/array');
    $sa = new PluginStringArray();
    $excel_data = $sa->from_excel_data(wfRequest::get('excel_data'));
    if($excel_data['columns']!=3){
      return array("alert('There should be 3 columns and not ".$excel_data['columns'].".')");
    }
    $id = wfRequest::get('id');
    $id = $this->replace_a_dot_to_slash($id);
    foreach($excel_data['data'] as $v){
      $la = $v[0];
      $key = $v[1];
      $value = $v[2];
      $i18n_yml = new PluginWfYml(wfGlobals::getAppDir().'/plugin/'.$id.'/i18n/'.$la.'.yml');
      $i18n_yml->set($key, $value);
      $i18n_yml->save();
    }
    return array("$('#modal_i18n_form').modal('hide')");
  }
  public function form_history_capture(){
    /**
     If version has a value we are updated, otherwise new history.
     */
    $id = wfRequest::get('id');
    $id = $this->replace_a_dot_to_slash($id);
    $version = wfRequest::get('version');
    if(!$version){
      $version = wfRequest::get('new_version');
    }
    $manifest = $this->yml_manifest($id);
    $data = array('date' => wfRequest::get('date'), 'title' => wfRequest::get('title'), 'description' => wfRequest::get('description'));
    if(wfRequest::get('webmaster')){
      $data['webmaster'] = wfRequest::get('webmaster');
    }
    /**
     * 
     */
    if(wfRequest::get('version')){
      $manifest->set("history/$version", $data);
    }else{
      $history = $manifest->get('history');
      $history = array_merge(array($version => $data), $history);
      $manifest->set("version", $version);
      $manifest->set("history", $history);
    }
    /**
     * 
     */
    $manifest->save();
    /**
     * 
     */
    return array("$('#modal_history_form').modal('hide')");
  }
  private function yml_manifest($id){
    $manifest = new PluginWfYml(wfGlobals::getAppDir()."/plugin/$id/manifest.yml");
    return $manifest;
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
    /**
     * Fix bug when '/' is part of array key.
     */
    wfPlugin::includeonce('wf/table');
    $this->plugin->set('public_folder_files', PluginWfTable::handle_array_keys($this->plugin->get('public_folder_files')));
    /**
     * readme
     * Replace in src to be able to display on github.
     * Replace [version]
     */
    $this->plugin->set('readme', wfPhpfunc::str_replace('src="public/', 'src="/plugin/'.$this->plugin->get('name').'/', $this->plugin->get('readme')));
    $this->plugin->set('readme', wfPhpfunc::str_replace('[version]', $this->plugin->get('manifest/version'), $this->plugin->get('readme')));
    $this->plugin->set('readme', wfPhpfunc::str_replace('[version_date]', $this->plugin->get('manifest/history/'.$this->plugin->get('manifest/version').'/date'), $this->plugin->get('readme')));
    /**
     * 
     */
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
    $element->setByTag(wfRequest::getAll(), 'request');
    $usage = $this->getUsage();
    $element->setByTag(array('usage' => $usage, 'has_usage' => sizeof($usage)));
    $element->setByTag(array('history' => $this->getHistory()));
    $element->setByTag($this->plugin->get('manifest'), 'manifest', true);
    $links = $this->getLinks();
    $element->setByTag(array('links' => $this->getLinks(), 'has_links' => sizeof($links)));
    $element->setByTag(array('theme_usage_url' => '/plugin_analysis/plugin_theme_usage/id/'.wfRequest::get('id')));
    $element->setByTag(array('i18n_url' => '/plugin_analysis/i18n/id/'.wfRequest::get('id')));
    $element->setByTag(array('public_folder_files_url' => '/plugin_analysis/public_folder_files/id/'.wfRequest::get('id')));
    wfDocument::renderElement($element->get());
  }
  public function page_public_folder_files(){
    $id = wfRequest::get('id');
    $id = $this->replace_a_dot_to_slash($id);
    $temp = $this->get_public_files($id);
    $temp2 = array();
    foreach($temp as $v){
      $v['plugin'] = $id;
      $temp2[] = $v;
    }
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($temp2));
  }
  public function page_public_folder_files_left(){
    $data = new PluginWfArray();
    $data->set('to', wfGlobals::getAppDir().'/plugin/'.wfRequest::get('plugin').'/public'.wfRequest::get('name'));
    $data->set('from', wfGlobals::getWebDir().'/plugin/'.wfRequest::get('plugin').''.wfRequest::get('name'));
    wfFilesystem::copyFile($data->get('from'), $data->get('to'));
    exit(json_encode(wfRequest::getAll()));
  }
  public function page_public_folder_files_delete(){
    $data = new PluginWfArray();
    $data->set('left', wfGlobals::getAppDir().'/plugin/'.wfRequest::get('plugin').'/public'.wfRequest::get('name'));
    $data->set('right', wfGlobals::getWebDir().'/plugin/'.wfRequest::get('plugin').''.wfRequest::get('name'));
    if(wfFilesystem::fileExist($data->get('left'))){
      wfFilesystem::delete($data->get('left'));
    }
    if(wfFilesystem::fileExist($data->get('right'))){
      wfFilesystem::delete($data->get('right'));
    }
    exit(json_encode(wfRequest::getAll()));
  }
  public function page_public_folder_files_right(){
    $data = new PluginWfArray();
    $data->set('from', wfGlobals::getAppDir().'/plugin/'.wfRequest::get('plugin').'/public'.wfRequest::get('name'));
    $data->set('to', wfGlobals::getWebDir().'/plugin/'.wfRequest::get('plugin').''.wfRequest::get('name'));
    wfFilesystem::copyFile($data->get('from'), $data->get('to'));
    exit(json_encode(wfRequest::getAll()));
  }
  public function page_plugin_theme_usage(){
    $this->setPlugin(array('theme_usage' => true));
    $element = new PluginWfYml(__DIR__.'/element/plugin_theme_usage.yml');
    $element->setByTag($this->plugin->get());
    wfDocument::renderElement($element->get());
  }
  public function page_i18n(){
    $id = wfRequest::get('id');
    $plugin_name = wfPhpfunc::str_replace('_A_DOT_', "/", $id);
    $i18n_folder = wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/i18n';
    $folder_exist = wfFilesystem::fileExist($i18n_folder);
    $result = new PluginWfArray();
    if($folder_exist){
      $i18n_files = wfFilesystem::getScandir($i18n_folder);
      /*
       * Add data.
       */
      foreach($i18n_files as $v){
        $data = new PluginWfYml($i18n_folder.'/'.$v);
        foreach($data->get() as $k2 => $v2){
          $result->set(wfPhpfunc::str_replace('.yml', '', $v).'_'.$k2, array('la' => wfPhpfunc::str_replace('.yml', '', $v), 'key' => $k2, 'value' => $v2));
        }
      }
      /*
       * Add empty rows.
       */
      foreach($result->get() as $v){
        foreach($i18n_files as $v2){
          $la = wfPhpfunc::str_replace('.yml', '', $v2);
          if( !wfPhpfunc::strstr($la, '_log') && !$result->get($la."_".$v['key']) ){
            $result->set($la."_".$v['key'], array('la' => $la, 'key' => $v['key'], 'value' => ''));
          }
        }
      }
      $temp = array();
      foreach($result->get() as $v){
        $search = '('.$v['la'].')';
        if(!wfPhpfunc::strlen($v['value'])){
          $search .= '(empty)';
        }
        $v['search'] = $search;
        $temp[] = $v;
      }
      $result = new PluginWfArray($temp);
      unset($temp);
    }
    $element = new PluginWfYml(__DIR__.'/element/'.__FUNCTION__.'.yml');
    $element->setByTag(array('data' => $result->get()));
    $element->setByTag(array('title' => 'i18n_'.$id));
    $element->setByTag(array('id' => $id));
    wfDocument::renderElement($element->get());
  }
  public function page_i18n_form(){
    wfPlugin::enable('form/form_v1');
    $element = new PluginWfYml(__DIR__.'/element/'.__FUNCTION__.'.yml');
    $element->setByTag(array('method' => 'render'));
    wfDocument::renderElement($element->get());
  }
  public function page_versions_update(){
    /**
     * 
     */
    $data = new PluginWfArray();
    /**
     * 
     */
    $this->setPlugin();
    /**
     * Must have clean repo.
     */
    if($this->plugins->get(wfRequest::get('id')."/git/has")!='Yes'){
      $data->set('clean_repo', false);
    }else{
      $data->set('clean_repo', true);
      $manifest = $this->update_manifest_versions(wfRequest::get('id'));
      $this->git_run(wfRequest::get('id'), $manifest->get('version'));
      $data->set('command', 'cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get(wfRequest::get('id')."/name").' && pwd && git push ');
    }
    /**
     * 
     */
    $element = new PluginWfYml(__DIR__.'/element/versions_update.yml');
    $element->setByTag($data->get());
    wfDocument::renderElement($element->get());
  }
  private function get_git_add_commit_push(){
    /**
     * 
     */
    $i = 0;
    $command = '';
    foreach($this->plugins->get() as $k => $v){
      /**
       * Must have git Yes ($type)
       */
      if($this->plugins->get("$k/git/has")!="Yes (changes)"){
        continue;
      }
      /**
       * 
       */
      $i++;
      /**
       * 
       */
      $command .= '&& cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$k/name").' ';
      $command .= '&& pwd ';
      $command .= '&& git add . ';
      $command .= '&& git commit -m "'.$v['version_manifest'].'" ';
      if($this->git_has_url_origin($v)){
        $command .= '&& git push ';
      }
    }
    /**
     * 
     */
    if($command){
      $command = wfPhpfunc::substr($command, 3);
    }
    /**
     * 
     */
    return new PluginWfArray(array('command' => $command, 'count' => $i));
  }
  public function page_git_add_commit_push(){
    /**
     * Set plugins.
     */
    $this->setPlugins();
    $result = $this->get_git_add_commit_push();
    /**
     * 
     */
    wfHelp::textarea_dump($result->get('command'));
    exit("Git (changed) done (".$result->get('count').")!");
  }
  private function git_has_url_origin($v){
    if(isset($v['git']['remote_get_url_origin']) && $v['git']['remote_get_url_origin']){
      return true;
    }else{
      return false;
    }
  }
  private function get_git_push_ahead($type, $action = 'command'){
    /**
     * 
     */
    $i = 0;
    $command = '';
    foreach($this->plugins->get() as $k => $v){
      /**
       * Must have git Yes ($type)
       */
      if($this->plugins->get("$k/git/has")!="Yes ($type)"){
        continue;
      }
      /**
       * 
       */
      $i++;
      /**
       * 
       */
      if($action==''){
        if($type=='ahead'){
          $this->git_push($k);
        }elseif($type=='behind'){
          $this->git_pull($k);
        }
      }else{
        if($type=='ahead'){
          $command .= '&& cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$k/name").' && pwd ';
          if($this->git_has_url_origin($v)){
            $command .= '&& git push ';
          }
        }elseif($type=='behind'){
          $command .= '&& cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$k/name").' && pwd && git pull ';
        }
      }
    }
    /**
     * 
     */
    if($command){
      $command = wfPhpfunc::substr($command, 3);
    }
    return new PluginWfArray(array('command' => $command, 'count' => $i));
  }
  public function page_git_push_ahead(){
    /**
     * action
     * If empty string we try to run git push commands.
     * If value "command" we output command script.
     */
    $action = wfRequest::get('action');
    $type = 'ahead';
    if(wfRequest::get('type')){
      $type = wfRequest::get('type');
    }
    /**
     * Set plugins.
     */
    $this->setPlugins();
    /**
     * 
     */
    $result = $this->get_git_push_ahead($type, $action);
    /**
     * 
     */
    if($action=='command'){
      wfHelp::textarea_dump($result->get('command'));
    }
    exit("Git $type done (".$result->get('count').")!");
  }
  private function get_git_fetch_all(){
    /**
     * 
     */
    $i = 0;
    $command = '';
    foreach($this->plugins->get() as $k => $v){
      if($this->plugins->get("$k/git/has")=="Yes" && $this->plugins->get("$k/git/remote_get_url_origin")){
        $i++;
        $command .= '&& cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$k/name").' && pwd && git fetch ';
      }
    }
    /**
     * 
     */
    if($command){
      $command = wfPhpfunc::substr($command, 3);
    }
    return new PluginWfArray(array('command' => $command, 'count' => $i));
  }
  public function page_git_fetch_all(){
    /**
     * Set plugins.
     */
    $this->setPlugins();
    /**
     * 
     */
    $result = $this->get_git_fetch_all();
    $element = new PluginWfYml(__DIR__.'/element/'.__FUNCTION__.'.yml');
    $element->setByTag(array('command' => $result->get('command'), 'count' => $result->get('count')));
    wfDocument::renderElement($element);
  }
  private function history_add_version($version, $history, $title = 'Versions', $description = 'Versions update.', $webmaster = ''){
    $new = array($version => array('date' => date('Y-m-d'), 'title' => $title, 'description' => $description));
    if($webmaster){
      $new[$version]['webmaster'] = $webmaster;
    }
    if($history){
      $history = array_merge($new, $history);
    }else{
      $history = $new;
    }
    return $history;
  }
  public function page_manifest_history_form(){
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function page_manifest_history_capture(){
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function manifest_history_capture(){
    $this->setPlugins();
    $temp = new PluginWfArray();
    foreach($this->plugins->get() as $k => $v){
      if($this->plugins->get("$k/git/has")=='Yes (changes)'){
        $temp->set($k, $v);
      }
    }
    $str = '';
    foreach($temp->get() as $k => $v){
      $str .= ','.$k;
      $this->update_manifest_history($k);
    }
    $str = substr($str, 1);
    return array("alert('". sizeof($temp->get()) ." plugins was updated ($str)!')");
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
    $command = '';
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
      $this->git_run($k, $manifest->get('version'));
      $command .= '&& cd '.wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$k/name").' && pwd ';
      if($this->git_has_url_origin($v)){
        $command .= '&& git push ';
      }
}
    /**
     * 
     */
    if($command){
      $command = wfPhpfunc::substr($command, 3);
    }
    /**
     * 
     */
    wfHelp::textarea_dump($command);
    /**
     * 
     */
    exit("Update versions done ($i)!");
  }
  private function git_run($plugins_key, $m){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash($plugins_key));
    $git->add();
    $git->commit($m);
    return null;
  }
  private function git_push($plugins_key){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash($plugins_key));
    $git->push();
    return null;
  }
  private function git_pull($plugins_key){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash($plugins_key));
    $git->pull();
    return null;
  }
  private function version_upgrade($version, $major = false){
    wfPlugin::includeonce('string/array');
    $obj = new PluginStringArray();
    /**
     * 
     */
    $version_a = ($obj->from_char(wfPhpfunc::str_replace('.', ':', $version), ':'));
    /**
     * 
     */
    if(!$major){
      if(sizeof($version_a)==3){
        $version_a[2]++;
        $version_a[2] = (string)$version_a[2];
      }elseif(sizeof($version_a)==2){
        $version_a[2] = '1';
      }elseif(sizeof($version_a)==1){
        if(!wfPhpfunc::strlen($version_a[0])){
          $version_a[0] = '1';
        }
        $version_a[1] = '0';
        $version_a[2] = '1';
      }
    }else{
      $version_a[1]++;
      $version_a[1] = (string)$version_a[1];
      $version_a[2] = '0';
    }
    /**
     *
     */
    $version = '';
    foreach($version_a as $v){
      $version .= '.'.$v;
    }
    $version = wfPhpfunc::substr($version, 1);
    return $version;
  }
  private function update_manifest_history($plugins_key){
    /**
     * Get plugin manifest.
     */
    $manifest = new PluginWfYml(wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$plugins_key/name").'/manifest.yml');
    if(wfRequest::get('version_type')=='Minor'){
      $manifest->set('version', $this->version_upgrade($manifest->get('version')));
    }else{
      $manifest->set('version', $this->version_upgrade($manifest->get('version'), true));
    }
    $manifest->set('history', $this->history_add_version($manifest->get('version'), $manifest->get('history'), wfRequest::get('title'), wfRequest::get('description'), wfRequest::get('webmaster')));
    $manifest->save();
    return null;
  }
  private function update_manifest_versions($plugins_key){
    /**
     * Get plugin manifest.
     */
    $manifest = new PluginWfYml(wfGlobals::getAppDir().'/plugin/'.$this->plugins->get("$plugins_key/name").'/manifest.yml');
    /**
     * 
     */
    if(!wfRequest::get('id')){
      /**
       * All plugin.
       */
      $manifest->set('version', $this->version_upgrade($manifest->get('version')));
      $manifest->set('history', $this->history_add_version($manifest->get('version'), $manifest->get('history')));
      foreach($this->plugins->get("$plugins_key/manifest/plugin") as $key => $value) {
        $i = new PluginWfArray($value);
        $version = $this->plugins->get(wfPhpfunc::str_replace('/', '.', $i->get('name')).'/manifest/version');
        $manifest->set("plugin/$key/name", $i->get('name'));
        $manifest->set("plugin/$key/version", $version);
      }
      $manifest->save();
      return $manifest;
    }else{
      /**
       * One plugin.
       */
      $manifest->set('version', $this->version_upgrade($manifest->get('version')));
      $manifest->set('history', $this->history_add_version($manifest->get('version'), $manifest->get('history')));
      foreach($this->plugin->get('manifest/plugin') as $key => $value) {
        $i = new PluginWfArray($value);
        $version = $this->plugins->get(wfPhpfunc::str_replace('/', '.', $i->get('name')).'/manifest/version');
        $manifest->set("plugin/$key/name", $i->get('name'));
        $manifest->set("plugin/$key/version", $version);
      }
      $manifest->save();
      return $manifest;
    }
  }
  public function page_js_include_method(){
    $this->setPlugin();
    $contents = file_get_contents(__DIR__.'/data/js_include_method.php');
    $contents = wfPhpfunc::str_replace("PluginXxxYyy.js", $this->plugin->get('js_name'), $contents);
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
      $contents = wfPhpfunc::str_replace("PluginXxxYyy", "Plugin".wfPlugin::to_camel_case($name), $contents);
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
     * Request param update could be empty or have value 1.
     */
    $this->setPlugin();
    /**
     * 
     */
    if($this->plugin->get('has_public_folder_twin') && !wfRequest::get('update')){
      exit('Has already public folder.');
    }
    /**
     * 
     */
    $id = wfRequest::get('id');
    $plugin_name = wfPhpfunc::str_replace('_A_DOT_', "/", $id);
    /**
     * Create copy array.
     */
    $copy = array();
    foreach ($this->plugin->get('files') as $key => $value) {
      if(wfPhpfunc::substr($key, 0, 8) != '/public/'){
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
    $plugin_name = wfPhpfunc::str_replace('_A_DOT_', "/", $id);
    $contents = file_get_contents(__DIR__.'/data/PluginXxxYyy.js');
    $contents = wfPhpfunc::str_replace("PluginXxxYyy", "Plugin".wfPlugin::to_camel_case($plugin_name), $contents);
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
    $plugin_name = wfPhpfunc::str_replace('_A_DOT_', "/", $id);
    $contents = file_get_contents(__DIR__.'/data/README.md');
    $contents = wfPhpfunc::str_replace("# Buto-Plugin-_", "# Buto-Plugin-".wfPlugin::to_camel_case($plugin_name), $contents);
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
      $plugin_name = wfPhpfunc::str_replace('_A_DOT_', "/", $id);
      $this->set_search_plugin($plugin_name);
      $temp = array();
      foreach ($this->plugin_search as $key => $value) {
        $version = $this->plugins->get(wfPhpfunc::str_replace("/", '.', $value[3]).'/version_manifest');
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
      $data->set('manifest/sys/0/name', wfGlobals::getVersion());
      $data->set('manifest/sys/0/version/0', wfGlobals::get('sys/version'));
      $data->set('manifest/php/version/0', wfGlobals::get('php/version'));
      /**
       * Create manifest.
       */
      $manifest = new PluginWfYml('/plugin/'.$plugin_name.'/manifest.yml');
      $manifest->set('', $data->get('manifest'));
      wfHelp::print($manifest);
      $manifest->save();
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
    $length = wfPhpfunc::strlen($content);
    for($i=0;$i<1000;$i++){
      $pos1 = strpos($content, $needle, $pos1+1);
      $pos2 = strpos($content, ')', $pos1+1);
      if($pos1===false){
        break;
      }
      $plugin = wfPhpfunc::substr($content, $pos1+strlen($needle), $pos2-$pos1-strlen($needle));
      $plugin = wfPhpfunc::str_replace("'", '', $plugin);
      $plugin = wfPhpfunc::str_replace('"', '', $plugin);
      /**
       * Fix if wfDocument::createWidget(.
       */
      if(wfPhpfunc::strstr($plugin, ',')){
        $plugin = wfPhpfunc::substr($plugin, 0, strpos($plugin, ','));
      }
      /**
       *
       */
      if(!wfPhpfunc::strstr($plugin, '$')){
        $this->plugin_search[] = array($needle, $pos1, $pos2, $plugin);
      }
    }
  }
  private function stat(){
    $stat = new PluginWfArray();
    $stat->set('conflict', 0);
    $stat->set('not_exist', 0);
    $stat->set('has_public_folder_text_yes_star', 0);
    foreach($this->plugins->get() as $k => $v){
      $i = new PluginWfArray($v);
      /**
       * conflict
       */
      if($i->get('conflict')=='Yes'){
        $stat->set('conflict', $stat->get('conflict')+1);
      }
      /**
       * public folder
       */
      if($i->get('has_public_folder_text')=='Yes*'){
        $stat->set('has_public_folder_text_yes_star', $stat->get('has_public_folder_text_yes_star')+1);
      }
      /**
       * Git
       */
      if($i->get('git/has')){
        if($stat->get('git_has_'.$i->get('git/has'))){
          $stat->set('git_has_'.$i->get('git/has'), $stat->get('git_has_'.$i->get('git/has'))+1);
        }else{
          $stat->set('git_has_'.$i->get('git/has'), 1);
        }
      }
      /**
       * not exist
       */
      if(!$i->get('exist')){
        $stat->set('not_exist', $stat->get('not_exist')+1);
        $this->plugins->set("$k/name", $this->plugins->get("$k/name").' (not_exist)');
      }
    }
    return $stat;
  }
  public function page_history_data(){
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    /**
     * Data
     */    
    if(wfRequest::get('load_history')=='yes'){
      $this->setPlugins();
    }
    $data = $this->history;
    exit($datatable->set_table_data($data));    
  }
  public function page_analys(){
    $this->setPlugins();
    $element = new PluginWfYml('/plugin/plugin/analysis/element/table.yml');
    $element->setByTag($this->stat()->get(), 'stat', true);
    /**
     * 
     */
    $git_add_commit_push = $this->get_git_add_commit_push();
    $element->setByTag($git_add_commit_push->get(), 'git_add_commit_push');
    $git_fetch_all = $this->get_git_fetch_all();
    $element->setByTag($git_fetch_all->get(), 'git_fetch_all');
    $git_push_ahead = $this->get_git_push_ahead('ahead', 'command');
    $element->setByTag($git_push_ahead->get(), 'git_push_ahead');
    $git_pull_behind = $this->get_git_push_ahead('behind', 'command');
    $element->setByTag($git_pull_behind->get(), 'git_pull_behind');
    /**
     * Buttons
     */
    foreach ($this->plugins->get() as $key => $value) {
      $action = new PluginWfYml('/plugin/plugin/analysis/element/table_tr_action.yml');
      $action->setByTag($value);
      $this->plugins->set("$key/table_tr_action", $action->get());
    }
    /**
     * 
     */
    $trs = array();
    foreach ($this->plugins->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $tr = new PluginWfYml('/plugin/plugin/analysis/element/table_tr.yml');
      $tr->setByTag($item->get());
      $tr->setByTag($item->get('git'), 'git', true);
      $trs[] = $tr->get();
    }
    $element->setByTag(array('trs' => $trs));
    $element->setByTag(wfRequest::getAll(), 'rs', true);
    wfDocument::renderElement($element->get());
  }
  private function setPlugin($data = array('has_public_folder' => true, 'has_manifest' => true, 'manifest_plugin' => true, 'theme_usage' => false, 'readme' => true, 'js' => true)){
    $data = new PluginWfArray($data);
    $id = wfRequest::get('id');
    $id = wfPhpfunc::str_replace('_A_DOT_', '.', $id);
    if(!wfRequest::get('cc')){
      $this->setPlugins(true);
    }else{
      $this->setPlugins();
    }
    $this->plugin = new PluginWfArray($this->plugins->get($id));
    /**
     * 1. has_public_folder
     */
    if($data->get('has_public_folder')){
      if($this->plugin->get('has_public_folder')){
        $files_left = $this->get_public_files($this->plugin->get('name'));
        /**
         * Set data.
         * (Does we need this any more due to loading data separate in datatable using ajax, 221017?)
         */
        $this->plugin->set('public_folder_files', $files_left);
      }else{
        /**
         * Set data.
         */
        $this->plugin->set('public_folder_files', array());
      }
    }
    /**
     * 2. has_manifest
     */
    if($data->get('has_manifest')){
      if($this->plugin->get('has_manifest')=='Yes'){
        $this->set_search_plugin($this->plugin->get('name'));
        if($this->plugin->get('manifest/plugin')){
          /*
           *
           */
          foreach ($this->plugin->get('manifest/plugin') as $k => $v) {
            $v['id_dot'] = wfPhpfunc::str_replace('/', '.', $v['name']);
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
              $version_manifest = $this->plugins->get($this->replace_slash_to_dot($v[3]).'/manifest/version');
              $this->plugin->set("manifest/plugin/", array('name' => $v[3], 'version' => null, 'version_manifest' => $version_manifest, 'find' => 'C'));
            }
          }
          /*
           * diff
           */
          foreach ($this->plugin->get('manifest/plugin') as $k => $v) {
            /**
             * version_diff
             */
            $version_diff = null;
            if($this->plugin->get("manifest/plugin/$k/version")!=$this->plugin->get("manifest/plugin/$k/version_manifest")){
              $version_diff = 'Yes';
            }
            $this->plugin->set("manifest/plugin/$k/version_diff", $version_diff);
            /**
             * conflict
             */
            $conflict = null;
            if(!$this->version_compare($this->plugin->get("manifest/plugin/$k/version"), $this->plugin->get("manifest/plugin/$k/version_manifest"))){
              $conflict = 'Yes';
            }
            $this->plugin->set("manifest/plugin/$k/conflict", $conflict);
          }
        }
      }
    }
    /**
     * 3. manifest_plugin
     */
    if($data->get('manifest_plugin')){
      if($this->plugin->get('manifest/plugin')){
        foreach ($this->plugin->get('manifest/plugin') as $key => $value) {
          $item = new PluginWfArray($value);
          /**
           * Click on name in cell
           */
          $this->plugin->set("manifest/plugin/$key/name_click", "<a href=\"#\" onclick=\"PluginPluginAnalysis.plugin('".str_replace("/", '.', $item->get('name'))."')\">".$item->get('name')."</a>");
        }
      }
    }
    /**
     * 4. theme_usage
     */
    if($data->get('theme_usage')){
      $theme_usage_temp = $this->getThemesUsingPlugin($id);
      //$theme_usage_temp = array();
      $theme_usage = array();
      foreach ($theme_usage_temp as $key => $value) {
        $theme_usage[] = $value;
      }
      $this->plugin->set('theme_usage', $theme_usage);
    }
    /**
     * 5. readme
     * Get README.md content.
     */
    if($data->get('readme')){
      $readme = null;
      $file = wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/readme.md';
      $exist = wfFilesystem::fileExist($file);
      if(!$exist){
        $file = wfGlobals::getAppDir().'/plugin/'.$this->plugin->get('name').'/README.md';
        $exist = wfFilesystem::fileExist($file);
      }
      if($exist){
        /**
         * 
         */
        $readme = file_get_contents($file);
        $readme2 = null;
        /**
         * 
         */
        wfPlugin::includeonce('string/array');
        $string_array = new PluginStringArray();
        $readme_links = null;
        foreach($string_array->from_br($readme) as $k => $v){
          if(wfPhpfunc::substr($v, 0, 1)=='#'){
            $readme_links .= '<a href="#anchor_'.$k.'">'.str_replace('#', '&nbsp;', $v).'</a><br>';
            $v .= '<a id="anchor_'.$k.'"></a>';
          }
          $readme2 .= $v."\n";
        }
        /**
         * 
         */
        wfPlugin::includeonce('readme/parser');
        $parser = new PluginReadmeParser();
        $readme2 = $parser->parse_text($readme2);
        $this->plugin->set('readme', $readme2);
        $this->plugin->set('has_readme', 'Yes');
        $this->plugin->set('readme_links', $readme_links);
      }else{
        $this->plugin->set('readme', $readme);
        $this->plugin->set('has_readme', 'No');
        $this->plugin->set('readme_links', null);
      }
    }
    /**
     * 6. js
     * Get Js.
     */
    if($data->get('js')){
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
    }
    /**
     * 
     */
    return null;
  }
  private function get_public_files($plugin_name){
    $has_public_folder_twin = is_dir(wfGlobals::getWebDir().'/plugin/'.$plugin_name);
    $files_right = array();
    if($has_public_folder_twin){
      $files_right = $this->scan_dir(wfGlobals::getWebDir().'/plugin/'.$plugin_name);
      foreach($files_right as $k => $v){
        $files_right[$k]['right_time'] = wfFilesystem::fileTime(wfGlobals::getWebDir().'/plugin/'.$plugin_name.$k);
      }
    }
    $files_left = array();
    if(wfFilesystem::fileExist(wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/public')){
      $files_left = $this->scan_dir(wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/public');
    }
    foreach($files_left as $k => $v){
      $files_left[$k]['left_time'] = wfFilesystem::fileTime(wfGlobals::getAppDir().'/plugin/'.$plugin_name.'/public'.$k);
      $files_left[$k]['left_time_text'] = null;
      $files_left[$k]['right_time'] = null;
      $files_left[$k]['right_time_text'] = null;
      $files_left[$k]['left_is_newer'] = null;
    }
    /**
     * Set right if exist on both.
     */
    foreach($files_left as $k => $v){
      $files_left[$k]['left'] = true;
      if(isset($files_right[$k])){
        $files_left[$k]['right'] = true;
        $files_left[$k]['size_right'] = $files_right[$k]['size'];
        $files_left[$k]['right_time'] = $files_right[$k]['right_time'];
      }
    }
    /**
     * Set right if exist on right.
     */
    foreach($files_right as $k => $v){
      if(!isset($files_left[$k])){
        $files_left[$k]['size_right'] = $files_right[$k]['size'];
        $files_left[$k]['right_time'] = $files_right[$k]['right_time'];
        $files_left[$k]['right'] = true;
        $files_left[$k]['left_time'] = null;
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
        $files_left[$k]['size_right'] = null;
      }elseif($i->get('right')){
        $files_left[$k]['exist'] = 'right';
        $files_left[$k]['size'] = null;
        $files_left[$k]['left_time_text'] = null;
        $files_left[$k]['left_is_newer'] = null;
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
     * Set time.
     */
    foreach($files_left as $k => $v){
      if($files_left[$k]['left_time']){
        $files_left[$k]['left_time_text'] = date('Y-m-d H:i:s', $files_left[$k]['left_time']);
      }
      if($files_left[$k]['right_time']){
        $files_left[$k]['right_time_text'] = date('Y-m-d H:i:s', $files_left[$k]['right_time']);
      }
      if(
        $files_left[$k]['left_time'] && 
        $files_left[$k]['right_time'] && 
        $files_left[$k]['left_time']>$files_left[$k]['right_time']){
        $files_left[$k]['left_is_newer'] = 'Yes';
      }
    }
    /**
     * 
     */
    return $files_left;
  }
  /**
   * Get themes used by a plugin.
   * @param string $plugin wf/example or wf.example
   * @return array
   */
  private function getThemesUsingPlugin($plugin){
    $plugin = wfPhpfunc::str_replace('/', '.', $plugin);
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
            $files[substr($info->getPathname(), wfPhpfunc::strlen($dir))]=array('size'=> filesize($info->getPathname()) );
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
      /**
       * Limit temporary if developer need it.
       */
      if($this->limit <= sizeof($plugin)){
        break;
      }
      /**
       * 
       */
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
    $v1_a = ($obj->from_char(wfPhpfunc::str_replace('.', ':', $v1), ':'));
    $v2_a = ($obj->from_char(wfPhpfunc::str_replace('.', ':', $v2), ':'));
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
    $this->history = array();
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
      $value_dot = wfPhpfunc::str_replace('/', '.', $value);
      $plugin->set($value_dot.'/name', $value);
      $plugin->set($value_dot.'/exist', true);
      /**
       * Git
       */
      $git->set_repo($value);
      $plugin->set($value_dot.'/git/status', null);
      $plugin->set($value_dot.'/git/has', null);
      $plugin->set($value_dot.'/git/log_date_last', null);
      $plugin->set($value_dot.'/git/active_branch', null);
      $plugin->set($value_dot.'/git/remote_get_url_origin', null);
      if($git->exist()){
        try {
          /**
           * 
           */
          $plugin->set($value_dot.'/git/status', $git->status());
          /**
           * 
           */
          if(wfPhpfunc::strstr($git->status(), 'Your branch is behind')){
            $plugin->set($value_dot.'/git/has', 'Yes (behind)');
          }elseif(wfPhpfunc::strstr($git->status(), 'Your branch is ahead of')){
            $plugin->set($value_dot.'/git/has', 'Yes (ahead)');
          }elseif(wfPhpfunc::strstr($git->status(), 'but the upstream is gone')){
            $plugin->set($value_dot.'/git/has', 'Yes (upstream)');
          }elseif(wfPhpfunc::strstr($git->status(), 'have diverged')){
            $plugin->set($value_dot.'/git/has', 'Yes (diverged)');
          }elseif(wfPhpfunc::strstr($git->status(), 'nothing to commit, working tree clean')){
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
          $plugin->set($value_dot.'/git/remote_get_url_origin', $git->remote_get_url_origin());
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
          $version_manifest = $this->plugins->get(wfPhpfunc::str_replace('/', '.', $plugin->get('name'))."/version_manifest");
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
      $this->plugins->set("$key/url_id", wfPhpfunc::str_replace('.', '_A_DOT_', $key));
    }
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
          $this->plugins->set(wfPhpfunc::str_replace('/', '.', $value2['name']).'/name', $value2['name']);
        }
      }
      /**
       * history
       */
      if(wfRequest::get('load_history')=='yes'){
        if($manifest->get('history')){
          foreach($manifest->get('history') as $k => $v){
            $i = new PluginWfArray($v);
            $i->set('plugin', $this->plugins->get($key.'/name'));
            $i->set('version', $k);
            $i->set('dot_plugin', $key);
            if(!$i->is_set('date')){
              $i->set('date', '');
            }
            if(!$i->is_set('title')){
              $i->set('title', '');
            }
            if(!$i->is_set('description')){
              $i->set('description', '');
            }
            $this->history[] = $i->get();
          }
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
    /**
     * 
     */
    wfPlugin::includeonce('string/array');
    $sa = new PluginStringArray();
    $status = $git->status();
    $status2 = $sa->from_br($status);
    $status3 = null;
    foreach($status2 as $v){
      if(wfPhpfunc::substr($v, 1, 12)=='modified:   '){
        $status3 .= '<a href="#" onclick="PluginPluginAnalysis.git_diff(this)" data-file="'.substr($v, 13).'" data-id="'.wfRequest::get('plugin').'">'.$v."</a>\n";
      }else{
        $status3 .= $v."\n";
      }
    }
    /**
     * 
     */
    $data->set('status', $status3);
    $data->set('log', $git->log());
    $data->set('remote_get_url_origin', $git->remote_get_url_origin());
    $element = new PluginWfYml(__DIR__.'/element/git.yml');
    $element->setByTag($data->get());
    $element->setByTag(wfRequest::getAll(), 'get');
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
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function page_git_pull(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->pull();
    wfDocument::renderElementFromFolder(__DIR__, __FUNCTION__);
  }
  public function page_git_fetch(){
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->fetch();
    exit('Fetch...');
  }
  public function page_git_commit(){
    if(!wfRequest::get('message') || wfRequest::get('message')=='null'){
      exit('Commit message missing...');
    }
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $git->commit(wfRequest::get('message'));
    exit('Commit...');
  }
  public function page_git_diff(){
    wfPlugin::includeonce('string/array');
    $sa = new PluginStringArray();
    $git = new PluginGitKbjr();
    $git->set_repo($this->replace_a_dot_to_slash(wfRequest::get('plugin')));
    $diff = $git->diff(wfRequest::get('filename'));
    $diff2 = $sa->from_br($diff);
    $diff3 = null;
    foreach($diff2 as $v){
      if(wfPhpfunc::substr($v, 0, 1)=='+'){
        $diff3 .= '<span style="color:green">'.$v."</span>\n";
      }elseif(wfPhpfunc::substr($v, 0, 1)=='-'){
        $diff3 .= '<span style="color:red">'.$v."</span>\n";
      }else{
        $diff3 .= $v."\n";
      }
    }
    $element = wfDocument::createHtmlElement('pre', $diff3);
    wfDocument::renderElement(array($element));
  }
  private function replace_a_dot_to_slash($str){
    $str = wfPhpfunc::str_replace('_A_DOT_', "/", $str);
    $str = wfPhpfunc::str_replace('.', "/", $str);
    return $str;
  }
  private function replace_slash_to_dot($str){
    $str = wfPhpfunc::str_replace('/', ".", $str);
    return $str;
  }
}
