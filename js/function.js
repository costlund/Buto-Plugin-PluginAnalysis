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
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: btn.innerHTML, url: '/plugin_analysis/theme_analys', resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close', size: 'sm'});
  }
  this.plugin = function(id){
    $('#modal_plugin').modal('hide');
    id = id.replace('.', '_A_DOT_');
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: 'Plugin', url: '/plugin_analysis/plugin/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close', size: 'lg'});
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
}
var PluginPluginAnalysis = new PluginPluginAnalysis();
