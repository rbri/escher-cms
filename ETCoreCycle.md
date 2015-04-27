# <et:core:cycle> #

## Namespace ##
core

## Type ##
Single

## Description ##
Cycles through a list of values. Each time the tag is executed, it will output the next value in the list of values provided via the **values** attribute. Once the list of values is exhausted, output will resume with the first value.

Named cycles allow multiple live cycles per page request. Use the **name** attribute to name a cycle.

To output random values, set the **random** attribute to true. A random element of the **values** list will be output with each execution. Note, random mode does **not** guarantee that all values will be used before a value is repeated.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|values|comma-separated list of values|values to cycle through|
|name|string (**"cycle"**)|unique name for this cycle, allows multiple active cycles|
|reset|true, **false**|whether to reset the cycle to its first value|
|random|true, **false**|whether to output a random element from the values list on each execution|

## Examples ##

Output a series of page links, each enclosed by a div element with an alternating css class.

```
	<et:pages:each>
		<div class='<et:core:cycle values="odd,even" />'>
			<et:pagelink />
		</div>
	</et:pages:each>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:cycle values="odd,even" />
```