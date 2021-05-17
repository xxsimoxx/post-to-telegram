=== Post to Telegram ===

Description:        Share your posts to your telegram channel. 
Version:            1.0.0
Requires PHP:       5.6
Requires:           1.1.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/post-to-telegram/releases/download/v1.0.0/post-to-telegram-1.0.0.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
    
Manage a directory of affiliates or partners.

== Description ==
# Plugin description

When editing a post, just check a checkbox and when you publish the post a link to it will be sent to your Telegram channel.
If there is one, post the featured image.

This plugin is written for ClassicPress.

## Usage

- Create your channel.
- Create a bot.
- Create a token for the bot
- Go to Settings -> Post to Telegram
- Fill your bot token.
- Fill your channel ID

Having troubles finding your channel ID? 
Try revoking admin rights to your boot, give it admin rights again, and save the changes leaving Channel field empty.
Maybe some hints will be shown!

## Filters

`ptt_query_params`
 
Change request made to Telegram Bot API.
_Example: adding text to the message_

```php
add_filter( 'ptt_query_params' , 'myprefix_telegram_before_text' , 10 , 3 );

public function myprefix_telegram_before_text( $params, $post_id, $method ) {
	if ( $method === 'sendMessage' ) {
		$params[ 'text' ] = '<b>Nuovo articolo!</b> ' . $params[ 'text' ];
	} else {
		$params[ 'caption' ] = '<b>Nuovo articolo!</b> ' . $params[ 'caption' ];
	}
	return $params;
}
```


### Privacy
**To help us know the number of active installations of this plugin, we collect and store anonymized data when the plugin check in for updates. The date and unique plugin identifier are stored as plain text and the requesting URL is stored as a non-reversible hashed value. This data is stored for up to 28 days.**


== Screenshots ==
1. Checkbox used to send to Telegram.
2. Post posted to Telegram.
3. Settings.


== Changelog ==
= 1.0.0 =
* First release.