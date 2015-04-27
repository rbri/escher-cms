# <et:core:image> #

## Namespace ##
core

## Type ##
Single

## Description ##
Outputs the content image specified by ID or by name.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|id|integer|if non-empty, the ID of the image to output|
|name|non-numeric string|if non-empty and no ID specified, the name of the image to output|
|title|string|if non-empty, this text will be used for the image tag's title attribute<br>otherwise the image's title will be used<br>
<tr><td>height</td><td>string</td><td>if non-empty, this text will be used for the image tag's height attribute<br>otherwise the image's height in pixels will be used</td></tr>
<tr><td>width</td><td>string</td><td>if non-empty, this text will be used for the image tag's width attribute<br>otherwise the image's width in pixels will be used</td></tr>
<tr><td><i>(tag atts)</i></td><td>string</td><td>any additional attributes will be passed directly to the <code>&lt;img&gt;</code> tag</td></tr></tbody></table>


<h2>Examples</h2>
Output an image, using the image's title as the value of the title attribute and with actual image height and width values in those attributes:<br>
<br>
<pre><code>	&lt;et:core:image id="37" /&gt;<br>
</code></pre>

Output an image, specifying various attributes for the img tag<br>
<br>
<pre><code>	&lt;et:core:image id="37" class="feature" title="Figure 2" /&gt;<br>
</code></pre>

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.<br>
<br>
<pre><code>	&lt;et:image id="37" /&gt;<br>
</code></pre>

<h2>Notes</h2>

The "images" namespace has its own "image" tag, with a different function, so do not use the above form inside the "images" namespace unless you really want <et:images:image>.<br>
<br>
In general it is preferable to specify images by ID instead of name. Both are unique identifiers, but ID will always refer to the same database record, whereas the name can be changed.<br>
<br>
This tag is for content images only. For design images, see <a href='ETDesignImage.md'>&lt;et:design:image&gt;</a>.<br>
<br>
<h2>See Also</h2>
<a href='ETDesignImage.md'>&lt;et:design:image&gt;</a><br>
<a href='ETNSImages.md'>&lt;et:ns:images&gt;</a><br>
<a href='ETImagesAlt.md'>&lt;et:images:alt&gt;</a><br>
<a href='ETImagesCount.md'>&lt;et:images:count&gt;</a><br>
<a href='ETImagesEach.md'>&lt;et:images:each&gt;</a><br>
<a href='ETImagesHeight.md'>&lt;et:images:height&gt;</a><br>
<a href='ETImagesIfCategory.md'>&lt;et:images:if_category&gt;</a><br>
<a href='ETImagesIfFirst.md'>&lt;et:images:if_first&gt;</a><br>
<a href='ETImagesIfLast.md'>&lt;et:images:if_last&gt;</a><br>
<a href='ETImagesImage.md'>&lt;et:images:image&gt;</a><br>
<a href='ETImagesTitle.md'>&lt;et:images:title&gt;</a><br>
<a href='ETImagesUrl.md'>&lt;et:images:url&gt;</a><br>
<a href='ETImagesWidth.md'>&lt;et:images:width&gt;</a><br>