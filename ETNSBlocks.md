# <et:ns:blocks> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "blocks" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:blocks>
		.
		.
		/* any non-scoped tags within this block will implicitly prefer the "blocks" namespace */
		.
		.
	</et:ns:blocks>
```

## Examples ##

The following example:

```
	<et:ns:blocks>
		<et:each>
			<et:title />
			<et:content />
		</et:each>
	</et:ns:blocks>
```

is equivalent to:

```
	<et:blocks:each>
		<et:blocks:title />
		<et:blocks:content />
	</et:blocks:each>
```