# <et:core:repeat> #

## Namespace ##
core

## Type ##
Container (Iterator)

## Description ##
Repeats a block of content a specified number of times. May be used in conjunction with the <et:core:index> tag, which outputs the value of the internal loop counter.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|start|integer (**1**)|start internal counter with this value|
|stop|integer|stop when internal counter reaches this value|


## Examples ##

```
	<et:core:repeat stop="10">
		<ul>
			<li>
				Page <et:core:index />
			</li>
		</ul>
	</et:core:repeat>
```

will output:

```
	<ul>
		<li>Page 1</li>
		<li>Page 2</li>
		<li>Page 3</li>
		<li>Page 4</li>
		<li>Page 5</li>
		<li>Page 6</li>
		<li>Page 7</li>
		<li>Page 8</li>
		<li>Page 9</li>
		<li>Page 10</li>
	</ul>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:repeat>
	</et:repeat>
```

## See Also ##
[<et:core:index>](ETCoreIndex.md)<br>