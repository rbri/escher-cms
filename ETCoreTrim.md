# <et:core:trim> #

## Namespace ##
core

## Type ##
Container

## Description ##
Removes white space from the beginning and end of the enclosed content block.

## Attributes ##
None

## Examples ##

Output the title of the current page context with no surrounding white space:

```
	<et:core:trim><et:pages:title /></et:core:trim>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:trim><et:pages:title /></et:trim>
```

## See Also ##
[<et:core:capitalize>](ETCoreCapitalize.md)<br>
<a href='ETCoreDowncase.md'>&lt;et:core:downcase&gt;</a><br>
<a href='ETCoreUpcase.md'>&lt;et:core:upcase&gt;</a><br>