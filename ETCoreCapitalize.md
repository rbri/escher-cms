# <et:core:capitalize> #

## Namespace ##
core

## Type ##
Container

## Description ##
Capitalizes each word in the enclosed content block.

## Attributes ##
None

## Examples ##

```
	<et:core:capitalize>This is a tesT.</et:core:capitalize>
```

will output:

```
	This Is A Test.
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:capitalize>This is a tesT.</et:capitalize>
```

## Notes ##
Every character that is not the first character of a word will be converted to lower-case.

## See Also ##
[<et:core:downcase>](ETCoreDowncase.md)<br>
<a href='ETCoreUpcase.md'>&lt;et:core:upcase&gt;</a><br>
<a href='ETCoreTrim.md'>&lt;et:core:trim&gt;</a><br>