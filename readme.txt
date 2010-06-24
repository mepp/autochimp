=== AutoChimp ===
Plugin Name: AutoChimp
Contributors: WandererLLC
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US
Tags: admin, email, MailChimp, Mail, Chimp
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

Gives users the ability to update their MailChimp mailing lists when users subscribe, unsubscribe, or update their WordPress profiles.

== Description ==

Automatically add, remove, and update users to your MailChimp mailing list as users subscribe and unsubscribe to your site.  AutoChimp uses a single options page to help you to simply and quickly manage your preferred settings.  In order to use AutoChimp, you must already have an account with MailChimp and at least one mailing list.

To use, save your MailChimp API Key on the options page then start adding your new registrations to any selected MailChimp mailing list.  You can configure the plugin to update your mailing list when 1) a new user subscribes, 2) a user unsubscribes, or 3) a user updates his information.  It's up to you to choose.

== Screenshots ==

1. The options page for AutoChimp works in a logical flow from top to bottom.  The first thing to do is to save your API key.  Once you do this, you only need to select the options that you want to support.

== Special Notes ==

Special Notes:

1)  Updating your mailing list when a user changes their profile has the potential to be problematic.  If you have alternate UIs or non-standard ways of updating users, then the correct sequence of calls may not happen and, as a result, the subscribed user will not be found in your MailChimp mailing list.  This is because there is the notion of an old email and a new email.  The old email must be fetched before the new email and if the plugin doesn't correctly pick up the old email, then it will be impossible to update a member.  The old email is fetched when the user's profile page is displayed.  The new email is saved when the user commits the update.

== Frequently Asked Questions ==

= How do I make suggestions or report bugs for this plugin? =

Just go to <http://www.wandererllc.com/company/plugins/autochimp/> and follow the instructions.

== License ==

This file is part of AutoChimp.

AutoChimp is free software:  you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.  

AutoChimp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See the license at <http://www.gnu.org/licenses/>.