# <et:core:escape\_html> #

## Namespace ##
core

## Type ##
Container

## Description ##
Escapes the enclosed content block so that it can be safely displayed to the user.

## Attributes ##
None

## Examples ##

The following example:

```
	<et:core:escape_html><div>This is a test</div></et:core:escape_html>
```

Produces:
```
	&lt;div&gt;This is a test&lt;/div&gt;
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:escape_html><div>This is a test</div></et:escape_html>
```

## Notes ##
In most cases, Escher will sanitize user input automatically. It is rarely necessary to invoke this tag directly.