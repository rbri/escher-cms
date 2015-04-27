# <et:ns:categories> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "categories" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:categories>
		.
		.
		/* any non-scoped tags within this block will implicitly prefer the "categories" namespace */
		.
		.
	</et:ns:categories>
```

## Examples ##

The following example:

```
	<et:ns:categories>
		<et:each>
			<et:name />
			<et:title />
		</et:each>
	</et:ns:categories>
```

is equivalent to:

```
	<et:categories:each>
		<et:categories:name />
		<et:categories:title />
	</et:categories:each>
```