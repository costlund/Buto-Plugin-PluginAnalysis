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
