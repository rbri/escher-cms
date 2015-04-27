# <et:phone:home> #

## Namespace ##
phone

## Type ##
Single or Container

## Description ##
Outputs a link to the site's home page. When used as a single tag, the site name from Site Preferences will be used as the link text. To specify custom link text, use as container tag and enclose the desired link text.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|full|**true**, false,|whether to output a full URL with scheme and host|

## Examples ##

The following example outputs a hyperlink to the site's home page.

```
	<et:phone:home />
```


The following example does the same with custom link text.

```
	<et:phone:home>
		Return to Home Page
	</et:phone:home>
```

## See Also ##
[<et:core:site\_url>](ETCoreSiteURL.md)