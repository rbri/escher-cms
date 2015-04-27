# <et:ns:design> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "design" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:design>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "design" namespace */
		.
		.
	</et:ns:design>
```

## Examples ##

The following example:

```
	<et:ns:design>
		<et:snippet name="header" />
	</et:ns:design>
```

is equivalent to:

```
	<et:design:snippet name="header" />
```