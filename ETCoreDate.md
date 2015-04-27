# <et:core:date> #

## Namespace ##
core

## Type ##
Single

## Description ##
Ouputs a time/date according to locale settings in the specified format and time zone.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|format (**"%A, %B %d, %Y"**)|string compatible with PHP's strftime function|format the date|
|date|string (**"now"**)|string compatible with PHP's strtotime function|
|timezone|string|time zone to convert to, defaults to timezone setting in admin preferences|

## Examples ##

The following example:

```
	<et:core:date />
```

Produces something like:
```
	Sunday, November 21, 2010
```

## Notes ##
A date tag also exists in the _pages_ namespace, which has higher default priority than the _core_ namespace. Therefore, you should not abbreviate this  tag by omitting the namespace. Unless you explicitly invoke the _core_ namespace, you will probably end up executing <et:pages:date> instead of <et:core:date>.

## See Also ##
[<et:pages:date>](ETPagesDate.md)<br>