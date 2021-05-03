<?php
/**
 * Plugin Name: Post to Telegram
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Share your posts to your telegram channel.
 * Version: 0.0.1
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 * Text Domain: ptt
 * Domain Path: /languages
 */

namespace XXSimoXX\PostToTelegram;

if (!defined('ABSPATH')) {
	die('-1');
}

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('classes/UpdateClient.class.php');

class PostToTelegram{

	private $default_options = [
		'bot-token'		=> '',
		'channel' 		=> '',
	];

	public function __construct() {

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Add settings page.
		add_action('admin_menu', [$this, 'add_settings_page']);

		// Add and handle send checkbox
		add_action('post_submitbox_misc_actions', [$this, 'add_publish_checkbox']);
		add_action('save_post', [$this, 'ptt_do'], 10, 3);

		// Uninstall.
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

	}

	public function text_domain() {
		load_plugin_textdomain('ptt', false, basename(dirname(__FILE__)).'/languages');
	}

	public function add_publish_checkbox($post_obj) {

		$options = get_option('ptt-config', $this->default_options);

		if ($options['bot-token'] === '' || $options['channel'] === '') {
			echo '<div class="ptt-div misc-pub-section misc-pub-section-last">';
			echo '<label><input type="checkbox" disabled value="1" name="ptt-do" />'.__('Review your config before posting to Telegram.', 'ptt').'</label>';
			echo $last_sent_message;
			echo '</div>';
			return;
		}

		$link = get_permalink($post_obj);

		if ($link === false) {
			return;
		}

		$last_sent = get_post_meta($post_obj->ID, 'ptt-last-sent', true);
		$last_sent_message = '';
		if ($last_sent !== false && $last_sent !== '') {
			$last_sent_message = '<br>'.__('Last time posted:', 'ptt').' '.$last_sent;
		}

		echo '<div class="ptt-div misc-pub-section misc-pub-section-last">';
		echo '<label><input type="checkbox" value="1" name="ptt-do" />'.__('Post to Telegram', 'ptt').'</label>';
		echo $last_sent_message;
		echo '</div>';

	}

	private function get_thumbnail_path($post_id) {
		if (!has_post_thumbnail($post_id)) {
			return false;
		}
		$thumb_id = get_post_thumbnail_id($post_id);
		if ($thumb_id === false || $thumb_id === 0) {
			return false;
		}
		$attach = get_attached_file($thumb_id);
		if ($attach === false) {
			return false;
		}
		return $attach;
	}

	public function ptt_do($post_id) {

		if (!isset($_POST['ptt-do']) || $_POST['ptt-do'] !== '1') {
			return;
		}

		$options = get_option('ptt-config', $this->default_options);

		if ($options['bot-token'] === '' || $options['channel'] === '') {
			return false;
		}

		$page_url = get_permalink($post_id);

		if ($page_url === false) {
			return false;
		}

		$image_path = $this->get_thumbnail_path($post_id);

		$params = [
			'chat_id'		=> $options['channel'],
			'parse_mode'	=> 'HTML',
		];

		if ($image_path === false) {
			$params['text']		= $page_url;
			$method				= 'sendMessage';
		} else {
			$params['photo']	= new \CURLFile($image_path);
			$params['caption']	= $page_url;
			$method				= 'sendPhoto';
		}

		$params = apply_filters('ptt_query_params', $params, $post_id, $method);

		$response = $this->telegram_curl($options['bot-token'], $method, $params);
		$result = json_decode($response, true);

		if ($result['ok'] === true) {
			update_post_meta($post_id, 'ptt-last-sent', date_i18n('D j F G:i'));
		}

		// TODO: gestisci errore ($posted === false) a questo punto
	}

	private function telegram_curl ($token, $method, $params = []) {
		$url = 'https://api.telegram.org/bot'.$token.'/'.$method;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$curl_result = curl_exec($ch);
		curl_close($ch);
		return $curl_result;
	}

	public function add_settings_page() {
		add_submenu_page('options-general.php', __('Post to Telegram settings', 'ptt'), __('Post to Telegram', 'ptt'), 'manage_options', 'post-to-telegram', [$this, 'render_settings_page']);
	}

	public function render_settings_page() {

		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized user');
		}

		if (isset($_POST['action']) && $_POST['action'] === 'ptt_save_options') {

			$new_options = $this->default_options;

			check_admin_referer('post_to_telegram', 'post_to_telegram_update_options');

			foreach (array_keys($this->default_options) as $option) {

				if (!isset($_POST[$option])) {
					continue;
				}

				$new_options[$option] = $_POST[$option];
			}

			update_option('ptt-config', $new_options);

			echo '<div class="notice notice-success is-dismissible go-away-soon">';
			echo '<p><strong>'.esc_html__('Settings have been saved.', 'ptt').'</strong></p></div>';
			echo '<script>jQuery(".go-away-soon").delay(3000).hide("slow", function() {});</script>';

		}

		$options = get_option('ptt-config', $this->default_options);

		echo '<div class="wrap">';
		echo '<h1>'.__('Post to Telegram settings', 'ptt').'</h1>';
		echo '<form method="post">';

		echo '<table class="form-table">';

		echo '<table class="form-table"><tr><th scope="row">';
		echo '<label for="bot-token">'.esc_html__('Bot token', 'ptt').'</label></th>';
		echo '<td><input name="bot-token" type="text" id="bot-token" value="'.$options['bot-token'].'" class="regular-text" />';
		echo '<p class="description">'.esc_html__('Secret token of the Bot you are using to post.', 'ptt').'</p></td></tr>';

		$tips = '';
		if ($options['bot-token'] !== '' && $options['channel'] === '') {
			$tips = $this->getChats($options['bot-token']);
		}

		echo '<tr><th scope="row">'.esc_html__('Dont\'t know what is your channel ID or something is not working?', 'ptt').'</th>';
		echo '<td>'.esc_html__('Leave channel fiels blank to get suggestions below.', 'ptt');
		echo '<p class="description">'.$tips.'</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">';
		echo '<label for="channel">'.esc_html__('Channel', 'ptt').'</label></th>';
		echo '<td><input name="channel" type="text" id="channel" value="'.$options['channel'].'" class="regular-text" />';
		echo '<p class="description">'.esc_html__('Channel (name or id) to post to.', 'ptt').'</p>'.$this->validateChat($options['bot-token'], $options['channel']).'</td></tr>';

		echo '</table>';

		echo '<input type="hidden" name="action" value="ptt_save_options" />';
		wp_nonce_field('post_to_telegram', 'post_to_telegram_update_options');
		submit_button();

		echo '</form></div>';
	}

	private function validateChat($token, $chat) {

		if ($chat === '') {
			return '';
		}

		$raw_data = $this->telegram_curl(
			$token,
			'getChatMember',
			[
				'chat_id' => $chat,
				'user_id' => preg_replace('/^([0-9]+):.*/', '\1', $token),
			 ]
		);

		$bot_data = json_decode($raw_data, false);

		if (! $bot_data->ok === true || !isset($bot_data->result->status) || $bot_data->result->status !== 'administrator') {
			return '<p class="error">'.esc_html__('Seems that your bot is not an administrator of the channel.', 'ptt').'</p>';
		}

		return '';

	}

	private function getChats($token) {

		$raw_data = $this->telegram_curl($token, 'getUpdates');
		$data = json_decode($raw_data, false);

		if ($data->ok !== true) {
			return esc_html__('Please check you bot token.', 'ptt');
		}

		$message = '';
		foreach ($data->result as $result) {

			if (!isset($result->my_chat_member->new_chat_member->status)) {
				continue;
			}

			if ($result->my_chat_member->new_chat_member->status !== 'administrator') {
				continue;
			}

			// Translators: 1 is channel name, 2 is channel ID
			$message .= sprintf(esc_html__('Your bot seems to be administrator of channel %1$s (Channel ID: %2$s).', 'ptt'), $result->my_chat_member->chat->title, $result->my_chat_member->chat->id).'<br />';

		}

		if ($message === '') {
			$message = esc_html__('No suggestion found. Try giving again your bot administration privileges in your channel.', 'ptt');
		}

		return $message;
	}

	private function warn($message, $line = false, $file = false) {

		if (!defined('WP_DEBUG') || WP_DEBUG !== true) {
			return;
		}

		$caller = debug_backtrace();
		if ($line === false) {
			$line = $caller[0]['line'];
		}
		if ($file === false) {
			$file = $caller[0]['file'];
		}

		if (function_exists('codepotent_php_error_log_viewer_log')) {
			return codepotent_php_error_log_viewer_log($message, 'notice', $file, $line);
		}

		$codepotent_file = plugin_dir_path(__DIR__).'codepotent-php-error-log-viewer/includes/functions.php';
		if (file_exists($codepotent_file)) {
			require_once($codepotent_file);
			return codepotent_php_error_log_viewer_log($message, 'notice', $file, $line);
		}

		trigger_error(print_r($x, true), E_USER_WARNING);

	}

	public static function uninstall() {
		delete_option('ptt-config');
	}

}

new PostToTelegram();