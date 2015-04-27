# <et:ns:links> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "links" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:links>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "links" namespace */
		.
		.
	</et:ns:links>
```

## Examples ##

The following example:

```
	<et:ns:links>
		<et:each>
			<et:title />
			<et:url />
		</et:each>
	</et:ns:links>
```

is equivalent to:

```
	<et:links:each>
		<et:links:title />
		<et:links:url />
	</et:links:each>
```