# <et:ns:user> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "user" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:user>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "user" namespace */
		.
		.
	</et:ns:user>
```

## Examples ##

The following example:

```
	<et:ns:user>
		<et:my_custom_tag />
	</et:ns:user>
```

is equivalent to:

```
	<et:user:my_custom_tag />
```

## Note ##

All user-defined tags reside in the "user" namespace. User-defined tags are created under the **Design** tab in the Echer CMS admin.