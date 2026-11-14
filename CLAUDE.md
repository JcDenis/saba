# saba

Notes for AI assistants working on the Dotclear plugin saba.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Replace public search page and 404 page by a custom page that show related entries to visitor.

No admin page. Public side: widgets.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/ActivityReportAction.php`: add 404 to activity report.
* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/UrlHandler.php`: frontend URL handler class.
* `src/Utils.php`: helpers.
* `src/Widgets.php`: widgets definition and rendering.
* `default-templates/`: public templates.
* Widgets: `saba`.
* Behaviors used: `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `initWidgets`, `urlHandlerBeforeGetData`, `coreBlogBeforeGetPosts`.

## Pitfalls

* None recorded yet: add here the traps met while working on the module.

## Testing

* Check the widgets (admin and public), in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
