# Buto-Plugin-PluginAnalysis


A page plugin to handle plugins. User must have role webmaster.


Param admin_layout is optional.

```
plugin_modules:
  plugin_analysis:
    plugin: 'plugin/analysis'
    settings:
      admin_layout: /theme/[theme]/layout/main.yml
```


## GIT

Supporting GIT.

## Webmaster

Add param to display elements.

```
webmaster:
  element:
    -
      type: strong
      innerHTML: Widget list.
```

## PHP

Get data from all plugins in system.

```
wfPlugin::includeonce('plugin/analysis');
$plugin_analysis = new PluginPluginAnalysis();
$plugin_analysis->setPlugins();
print_r($plugin_analysis->plugins->get());
```
