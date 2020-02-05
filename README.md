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
wfRequest::set('theme', wfGlobals::getTheme());
$plugin_analysis->setPlugins();
print_r($plugin_analysis->plugins->get());
```

This is to only ask for plugins used by current theme.

```
wfRequest::set('theme', wfGlobals::getTheme());
```
## Cache

Plugin data file (/../buto_data/theme/_theme_/plugin_analysis_plugins.yml) is created when plugin are listed. A button can be used to clear cache in plugin view.