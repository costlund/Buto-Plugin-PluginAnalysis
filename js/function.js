function PluginPluginAnalysis(){
  this.reload = function(){
    PluginWfAjax.update('start');
  }
  this.analys = function(theme){
    if(theme){
      PluginWfAjax.load('start', '/[[class]]/analys?theme='+theme);
      $('#modal_plugin').modal('hide');
    }else{
      PluginWfAjax.load('start', '/[[class]]/analys');
    }
  }
  this.theme_analys = function(btn){
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: btn.innerHTML, url: '/plugin_analysis/theme_analys', size: 'sm'});
  }
  this.plugin = function(id){
    $('#modal_plugin').modal('hide');
    id = id.replace('.', '_A_DOT_');
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: 'Plugin', url: '/plugin_analysis/plugin/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close', size: 'xl'});
  }
  this.plugin_clear_cache = function(id){
    $('#modal_plugin').modal('hide');
    id = id.replace('.', '_A_DOT_');
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: 'Plugin', url: '/plugin_analysis/plugin/id/'+id+'/cc/1', resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close', size: 'xl'});
  }
  this.manifest_create = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_manifest_create', label: 'Manifest create', url: '/plugin_analysis/manifest_create/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.readme_create = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_manifest_create', label: 'Readme create', url: '/plugin_analysis/readme_create/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.js_create = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_js_create', label: 'Js create', url: '/plugin_analysis/js_create/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.js_include_method = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_js_include_method', label: 'Js include method', url: '/plugin_analysis/js_include_method/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.public_create = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_public_create', label: 'Public create', url: '/plugin_analysis/public_create/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.public_update = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_public_create', label: 'Public update', url: '/plugin_analysis/public_create/id/'+id+'?update=1', resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.plugin_create = function(){
    PluginWfBootstrapjs.modal({id: 'modal_plugin_create', label: 'Plugin create', url: '/plugin_analysis/plugin_create'});
  }
  this.plugin_create_run = function(name){
    $.getJSON( "/plugin_analysis/plugin_create_run?name="+name, function( data ) {
      if(data.success){
        $('#modal_plugin_create').modal('hide');
      }else{
        alert(data.error);
      }
    });
  }
  /**
   * Git
   */
  this.git = function(btn, action){
    var plugin = btn.getAttribute('data-id');
    var version_manifest = btn.getAttribute('data-version_manifest');
    if(action==''){
      PluginWfBootstrapjs.modal({id: 'modal_plugin_git', label: 'GIT', url: '/plugin_analysis/git?plugin='+plugin+'&version_manifest='+version_manifest, btn_reload: true, size: 'xl'});
    }else if(action=='pull'){
      this.git_pull(btn);
    }else if(action=='push'){
      this.git_push(btn);
    }
  }
  this.git_add = function(btn){
    var plugin = btn.getAttribute('data-id');
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_add', label: 'GIT add', url: '/plugin_analysis/git_add?plugin='+plugin});
  }
  this.git_push = function(btn){
    var plugin = btn.getAttribute('data-id');
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_push', label: 'GIT push', url: '/plugin_analysis/git_push?plugin='+plugin});
  }
  this.git_pull = function(btn){
    var plugin = btn.getAttribute('data-id');
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_pull', label: 'GIT pull', url: '/plugin_analysis/git_pull?plugin='+plugin});
  }
  this.git_fetch = function(btn){
    var plugin = btn.getAttribute('data-id');
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_fetch', label: 'GIT fetch', url: '/plugin_analysis/git_fetch?plugin='+plugin});
  }
  this.git_commit = function(btn){
    var plugin = btn.getAttribute('data-id');
    var message = prompt("Commit message", btn.getAttribute('data-version_manifest'));
    if(!message){
      return null;
    }
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_commit', label: 'GIT commit', url: '/plugin_analysis/git_commit?plugin='+plugin+'&message='+message});
  }
  this.git_diff = function(btn){
    var plugin = btn.getAttribute('data-id');
    var filename = btn.getAttribute('data-file');
    if(!filename){
      filename = prompt("File name", "");
    }
    if(!filename){
      return null;
    }
    PluginWfBootstrapjs.modal({id: 'modal_plugin_git_diff', label: 'GIT diff', url: '/plugin_analysis/git_diff?plugin='+plugin+'&filename='+filename, size: 'xl'});
  }
  this.versions_update = function(btn){
    var plugin = btn.getAttribute('data-id');
    PluginWfBootstrapjs.modal({id: 'modal_versions_update', label: 'Update versions', url: '/plugin_analysis/versions_update?id='+plugin});
  }
  this.versions_update_all = function(btn){
    PluginWfBootstrapjs.modal({id: 'modal_versions_update_all', label: 'Update versions', url: '/plugin_analysis/versions_update_all'});
  }
  this.git_push_ahead = function(btn, action){
    /**
     * 
     */
    if(typeof action == 'undefined'){
      action = '';
    }
    PluginWfBootstrapjs.modal({id: 'modal_git_push_ahead', label: btn.innerHTML, url: '/plugin_analysis/git_push_ahead?action='+action});
  }
  this.git_pull_behind = function(btn, action){
    /**
     * 
     */
    if(typeof action == 'undefined'){
      action = '';
    }
    PluginWfBootstrapjs.modal({id: 'modal_git_pull_behind', label: btn.innerHTML, url: '/plugin_analysis/git_push_ahead?type=behind&action='+action});
  }
  this.git_fetch_all = function(btn, action){
    /**
     * 
     */
    PluginWfBootstrapjs.modal({id: 'modal_git_fetch_all', label: btn.innerHTML, url: '/plugin_analysis/git_fetch_all'});
  }
  this.history_form = function(id, version){
    if(typeof version == 'undefined'){
      version = '';
    }
    PluginWfBootstrapjs.modal({id: 'modal_history_form', label: 'History', url: '/plugin_analysis/history_form?id='+id+'&version='+version});
  }
  this.i18n_form = function(btn){
    PluginWfBootstrapjs.modal({id: 'modal_i18n_form', label: btn.innerHTML, url: '/plugin_analysis/i18n_form?id='+btn.getAttribute('data-id'), size: 'xl'});
  }
  this.git_add_commit_push = function(btn){
    /**
     * 
     */
    if(typeof action == 'undefined'){
      action = '';
    }
    PluginWfBootstrapjs.modal({id: 'modal_git_add_commit_push', label: btn.innerHTML, url: '/plugin_analysis/git_add_commit_push?action='+action});
  }
  this.dt_public_folder_files_data = {};
  this.dt_public_folder_files_row_click = function(data){
    /**
     * 
     */
    this.dt_public_folder_files_data = data;
    /**
     * 
     */
    PluginWfBootstrapjs.modal({id: 'modal_public_folder_files_one', label: data.name, content: ''});
    /**
     * 
     */
    var element = [
      {
        type: 'a', 
        attribute: {onclick: "PluginPluginAnalysis.dt_public_folder_files_row_click_left()", class: 'btn btn-primary'}, 
        innerHTML: 'Copy to left'
      },
      {
        type: 'a', 
        attribute: {onclick: "PluginPluginAnalysis.dt_public_folder_files_row_click_delete()", class: 'btn btn-warning'}, 
        innerHTML: 'Delete'
      },
      {
        type: 'a', 
        attribute: {onclick: "PluginPluginAnalysis.dt_public_folder_files_row_click_right()", class: 'btn btn-primary', style: 'float:right'}, 
        innerHTML: 'Copy to right'
      }
    ];
    /**
     * 
     */
    PluginWfDom.render(element, document.getElementById('modal_public_folder_files_one_body'));
  }
  this.dt_public_folder_files_row_click_left = function(){
    $.getJSON( "/plugin_analysis/public_folder_files_left?"+$.param(this.dt_public_folder_files_data), function( data ) {
      if(data.success){
        console.log(data);
        $('#modal_public_folder_files_one').modal('hide');
      }else{
        alert(data.error);
      }
    });
    return null;
  }
  this.dt_public_folder_files_row_click_delete = function(){
    if(!confirm('Are you sure to DELETE?')){
      return null;
    }
    $.getJSON( "/plugin_analysis/public_folder_files_delete?"+$.param(this.dt_public_folder_files_data), function( data ) {
      if(data.success){
        console.log(data);
        $('#modal_public_folder_files_one').modal('hide');
      }else{
        alert(data.error);
      }
    });
    return null;
  }
  this.dt_public_folder_files_row_click_right = function(){
    $.getJSON( "/plugin_analysis/public_folder_files_right?"+$.param(this.dt_public_folder_files_data), function( data ) {
      if(data.success){
        console.log(data);
        $('#modal_public_folder_files_one').modal('hide');
      }else{
        alert(data.error);
      }
    });
    return null;
  }
}
var PluginPluginAnalysis = new PluginPluginAnalysis();
