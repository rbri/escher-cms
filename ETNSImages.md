# <et:ns:images> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "images" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:images>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "images" namespace */
		.
		.
	</et:ns:images>
```

## Examples ##

The following example:

```
	<et:ns:images>
		<et:each>
			<et:title />
			<et:url />
		</et:each>
	</et:ns:images>
```

is equivalent to:

```
	<et:images:each>
		<et:images:title />
		<et:images:url />
	</et:images:each>
```