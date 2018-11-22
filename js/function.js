function PluginPluginAnalysis(){
  this.analys = function(){
    PluginWfAjax.load('start', '/[[class]]/analys');
  }
  this.plugin = function(id){
    id = id.replace('.', '_A_DOT_');
    PluginWfBootstrapjs.modal({id: 'modal_plugin', label: 'Plugin', url: '/plugin_analysis/plugin/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
  this.manifest_create = function(element){
    var id = element.getAttribute('data-url_id');
    PluginWfBootstrapjs.modal({id: 'modal_manifest_create', label: 'Manifest create', url: '/plugin_analysis/manifest_create/id/'+id, resizable: true, footer: '', footer_btn_close: true, footer_btn_close_text: 'Close'});
  }
}
var PluginPluginAnalysis = new PluginPluginAnalysis();






