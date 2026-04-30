=== Get Image from Post ===
Contributors: horshipsrectors
Plugin URI:
Tags: adopt-me
Donate link:
Requires at least: 4.0.0
Tested up to: 4.8.0
Stable tag: 2017.08.13

Allows users to fetch an image from a post within the Loop.

== Description ==


** this plugin is no longer being update. Please feel free to adopt me! **



This is a simple plugin which allows users to return an image from the related post.



== Installation ==

To install the plugin, please upload the folder to your plugins folder and active the plugin.

== Screenshots ==




== Frequently Asked Questions ==

= How do I display the results? =

Insert the following code into your WordPress theme files:

= General results =
Without passing any parameters, the plugin will return ten results or fewer depending on how many posts you have.

 horshipsrectors_get_image_from_post();


= Altering the before and after values =
By default the plugin wraps your code in list item (&lt;li&gt;) tags but you can specify how to format the results using the following code:

 horshipsrectors_get_image_from_post('before=&lt;p&gt;&amp;after=&lt;/p&gt;');

= Adding a Link =
If you'd like to link to the post (remember it's not live yet) you can do so by calling:

 horshipsrectors_get_image_from_post('link=true');


= Which image? =
You can specify which image is returned using the code:

 horshipsrectors_get_image_from_post('image=2');

= Strip Attributes =
If you would like to strip the attributes such as width and height from the returned value:

 horshipsrectors_get_image_from_post('strip=true');

= Echo vs. Return =
Finally, if you'd like to copy the results into a variable you can return the results as follows:

 horshipsrectors_get_image_from_post('show=false');





== Change Log ==

= 2.0.0 =

* renamed function to be more compatible.
* tested and optimized in WordPress 3.2
* added test to determine in images existed

= 1.0.0 =

* official release
* added strip attribute

= 1.1.0 =

* minor fixes for wordpress new admin
