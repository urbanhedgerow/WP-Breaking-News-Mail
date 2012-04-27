=== Plugin Name ===
Contributors: Dahe  
Tags: breaking news email, email notification alerts
Requires at least: 2.0.2
Tested up to: 3.3


This is a plugin which sends an email to subscribers each time a Breking News is posted. 

== Description ==

In practical words, you can send an email to your subscribers each time a post is published on one or more categories.

The format of the email can also be customised and you can generate emails for each of the following formats:
* plaintext excerpt
* HTML excerpt 

*Important note:* The email alert will be sent only when you publish a post, not on updates. I recommend you create a single category destinated to this email alerts and check it in the plugin settings page, this way, when a breaking new occur you can check this category the email will be sent to your subscribers.

You can edit the email templates. The messages in the public form for subscriptions are being shown with ajax

Based on Subscribe2: http://subscribe2.wordpress.com/ from Matthew Robinson

If you have any issue, you can tell me about it through this link: https://github.com/DanielaValero/WP-Breaking-News-Mail/issues

== Installation ==
1. Log in to your WordPress blog and visit Plugins->Add New.
2. Search for Breaking News Mail, click "Install Now" and then Activate the Plugin
3. Click the "Breaking Settings" admin menu link.
4. Configure the options to taste, including the email template and any categories which should be included in the email alert.
5. Create a WordPress Page to display the feedback after the subscription/unsubscription email confirmation. When creating the page, manually insert the shortcode: [BNM_CONFIRMATION_MESSAGE] This token will automatically be replaced and will display the confirmation messages as necessary.
6. In the WordPress "Settings" area for Breaking News Email select the page name in the "Appearance" section that of the WordPress page created in step 5.

== Screenshots ==

1. Settings page
2. Subscribers page
3. Widget section
4. Page with the shortcode
5. Widget shown on the home page
6. Email alert example

== Changelog ==

= 1.0 =
* This is the first release

1.01
* The email alerts are sent only on post publish, not on updates
* Added I18n

1.02
* Added support for PHP versions > 5.2

1.03
* Some bugs solved