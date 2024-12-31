<?php /**
 * GitHub Plugin Updater 2024.11.24
 * To use in another plugin make sure you rename the namespace to match plugin
 */

namespace CreativeSlice\WPAdmin;
$path = plugin_dir_path(__FILE__) .'parsedown/Parsedown.php';
include_once $path;

use CreativeSlice\WPAdmin\parsedown\Parsedown;


class Plugin_Updater {

	/**
	 * Plugin directory
	 *
	 * @var string github-updater-demo
	 */
	private string $pluginDir = '';
	/**
	 * Plugin file
	 *
	 * @var string github-updater-demo/github-updater-demo.php
	 */
	private string $pluginFile = '';
	private string $pluginFilename = '';

	private $plugin_slug;
    private $plugin_data;
    private $github_api_url;
    private $github_token;

	private $is_built = false;

	/**
	 * Relative path to plugin icon from plugin root.
	 *
	 * @var string assets/icon.png
	 */
	private string $pluginIcon = '';

	/**
	 * Relative path to small plugin banner from plugin root.
	 *
	 * @var string assets/banner-772x250.jpg
	 */
	private string $pluginBannerSmall = '';

	/**
	 * Relative path to large plugin banner from plugin root.
	 *
	 * @var string assets/banner-1544x500.jpg
	 */
	private string $pluginBannerLarge = '';

    public function __construct($plugin_file, $github_repo) {
		$this->pluginFile = str_replace(
			WP_PLUGIN_DIR . '/', '', $plugin_file
		);

		// e.g. `github-updater-demo` and `github-updater-demo.php`
		[$this->pluginDir, $this->pluginFilename] = explode(
			'/', $this->pluginFile
		);

		$this->plugin_slug = plugin_basename($plugin_file);
        $this->github_api_url = "https://api.github.com/repos/{$github_repo}/releases/latest";


		$this->init_action_filters($plugin_file);


	}

	/**
	 * if the plugin is one that has a build process as part of the release due to
	 * having custom ui elements or creates custom blocks you will want to use this
	 * @see TBD
	 * @return $this
	 */
	public function plugin_is_built() {
		$this->is_built = true;
		return $this;
	}

	/**
	 * set the github token for pricate repo access
	 * @param string $token
	 * @return $this
	 */
	public function set_token($token) {
		$this->github_token = $token;
		return $this;
	}

	public function set_plugin_icon(string $file): self
	{
		$this->pluginIcon = ltrim($file, '/');

		return $this;
	}

	/**
	 * Set relative path to small plugin banner from plugin root.
	 *
	 * @param string $file assets/banner-772x250.jpg
	 * @return $this
	 */
	public function set_plugin_banner_small(string $file): self
	{
		$this->pluginBannerSmall = ltrim($file, '/');

		return $this;
	}

	/**
	 * Set relative path to large plugin banner from plugin root.
	 *
	 * @param string $file assets/banner-1544x500.jpg
	 * @return $this
	 */
	public function set_plugin_banner_large(string $file): self
	{
		$this->pluginBannerLarge = ltrim($file, '/');

		return $this;
	}

	/**
	 * Get plugin icon if defined and valid.
	 *
	 * @return string https://example.org/wp-content/plugins/github-updater-demo/assets/icon.png
	 */
	private function get_plugin_icon(): string
	{
		if (!$this->pluginIcon) return '';

		$pluginIconPath = $this->pluginDir . '/' . $this->pluginIcon;

		return plugins_url($pluginIconPath);
	}

	/**
	 * Get small plugin banner (772x250).
	 *
	 * @return string https://example.org/wp-content/plugins/github-updater-demo/assets/banner-772x250.jpg
	 */
	private function get_plugin_banner_small(): string
	{
		if (!$this->pluginBannerSmall) return '';

		return $this->get_plugin_file($this->pluginBannerSmall);
	}

	/**
	 * Get plugin file if exists.
	 *
	 * @param string $file assets/icon.png
	 * @return string https://example.org/wp-content/plugins/github-updater-demo/assets/icon.png
	 */
	private function get_plugin_file(string $file): string
	{
		$file = sprintf('%s/%s', $this->pluginDir, $file);

		if (!file_exists(WP_PLUGIN_DIR . '/' . $file)) return '';

		return plugins_url($file);
	}


	/**
	 * Get large plugin banner (1544x500).
	 *
	 * @return string https://example.org/wp-content/plugins/github-updater-demo/assets/banner-1544x500.jpg
	 */
	private function get_plugin_banner_large(): string
	{
		if (!$this->pluginBannerLarge) return '';

		return $this->get_plugin_file($this->pluginBannerLarge);
	}

	/**
	 * Hook to build plugin details result.
	 *
	 * @param array|false|object $result ['name' => 'GitHub Updater Demo', ...]
	 * @param string $action plugin_information
	 * @param object $args ['slug' => 'ryansechrest-github-updater-demo', ...]
	 * @return array|false|object ['name' => 'GitHub Updater Demo', ...]
	 */
	public function get_plugin_details_report(
		array|false|object $result, string $action, object $args
	): array|false|object
	{
		// If action is query_plugins, hot_tags, or hot_categories, exit
		if ($action !== 'plugin_information') return $result;

		// If not our plugin, exit
		if ($args->slug !== $this->plugin_slug) return $result;

		// Get remote plugin file contents to read plugin header
		$release_info = $this->get_github_release_info();


		// If remote plugin file could not be retrieved, exit
		if (!$release_info) return $result;



		// Build plugin detail result
		$result = [
			'name' => $this->plugin_data['Name'],
			'slug' => $this->plugin_slug,
			'version' => $release_info->tag_name,
			'requires' =>  $this->plugin_data['RequiresWP'],
			'requires_php' =>  $this->plugin_data['RequiresPHP'],
			'homepage' =>  $this->plugin_data['PluginURI'],
			'sections' => [],
		];

		// Assume no author
		$author = '';

		// If author name exists, use it
		if ( $this->plugin_data['AuthorName']) {
			$author =  $this->plugin_data['AuthorName'];
		}

		// If author name and URL exist, use them both
		if ( $this->plugin_data['AuthorName'] &&  $this->plugin_data['AuthorURI']) {
			$author = sprintf(
				'<a href="%s">%s</a>',
				$this->plugin_data['AuthorURI'],
				$this->plugin_data['AuthorName']
			);
		}

		// If author exists, set it
		if ($author) {
			$result['author'] = $author;
		}

		// If small plugin banner exists, set it
		if ($pluginBannerSmall = $this->get_plugin_banner_small()) {
			$result['banners']['low'] = $pluginBannerSmall;
		}

		// If large plugin banner exists, set it
		if ($pluginBannerLarge = $this->get_plugin_banner_large()) {
			$result['banners']['high'] = $pluginBannerLarge;
		}

		if ($pluginIcon = $this->get_plugin_icon()) {
			$result['icons']['default'] = $pluginIcon;
		}

		// If release notes exist, set them as the changelog
		if ($changelog = $release_info->body) {
			$Parsedown = new Parsedown();
			$result['sections']['changelog'] = $Parsedown->text($changelog);
		}

		return (object) $result;
	}

    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release_info = $this->get_github_release_info();
        if ($release_info && version_compare(ltrim($release_info->tag_name, 'v'), $this->plugin_data['Version'], '>')) {
            $download_link = $this->get_download_link($release_info);
            if ($download_link) {
                $transient->response[$this->plugin_slug] = (object) [
                    'slug' => $this->plugin_slug,
                    'plugin' => $this->plugin_slug,
                    'new_version' => ltrim($release_info->tag_name, 'v'),
                    'package' => $download_link,
                    'tested' => $this->plugin_data['Tested up to'] ?? '',
                    'requires' => $this->plugin_data['Requires at least'] ?? '',
                    'requires_php' => $this->plugin_data['Requires PHP'] ?? '',
					'sections' => [
						'description' => $this->plugin_data['Description'],
						'changelog' => $release_info->body,
					],
                ];
            }
        }

        return $transient;
    }

    private function get_github_release_info() {
		$options = $this->get_http_request_options();

		$response = wp_remote_get($this->github_api_url, $options);


        if (is_wp_error($response)) {
            return false;
        }

		$release_info = json_decode(wp_remote_retrieve_body($response));
		if (isset($release_info->tag_name)) {
			return $release_info;
		}

		return false;
    }

    private function get_download_link($release_info) {
        foreach ($release_info->assets as $asset) {
            if (substr($asset->name, -4) === '.zip') {
                return $asset->browser_download_url;
            }
        }
        return $release_info->zipball_url ?? null;
    }

    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') === false) {
            return $reply;
        }

        $upgrader->skin->feedback("Starting download from GitHub...");

        $url_parts = parse_url($package);
        $path_parts = explode('/', trim($url_parts['path'], '/'));
        $api_url = "https://api.github.com/repos/{$path_parts[0]}/{$path_parts[1]}/releases/tags/{$path_parts[4]}";
		$response = wp_remote_get($api_url, $this->get_http_request_options(['headers' => ['Accept' => 'application/vnd.github+json']]));

        if (is_wp_error($response)) {
            return new \WP_Error('release_info_failed', 'Failed to get release information.');
        }

        $release_info = json_decode(wp_remote_retrieve_body($response), true);
        $download_url = '';
        foreach ($release_info['assets'] as $asset) {
            if ($asset['name'] === basename($package)) {
                $download_url = $asset['url'];
                break;
            }
        }

        if (empty($download_url)) {
            return new \WP_Error('asset_not_found', 'The specified asset was not found in the release.');
        }
		$options = $this->get_http_request_options([
			'timeout' => 300,
			'stream' => true,
			'filename' => wp_tempnam('github_update'),
			'headers' => [
				'Accept' => 'application/octet-stream',
			]
		]);
        $download_response = wp_remote_get($download_url, $options);

        if (is_wp_error($download_response)) {
            return new \WP_Error('download_failed', 'Failed to download the update package.');
        }

        return $download_response['filename'];
    }

    public function http_request_args($args, $url) {
        if (strpos($url, 'api.github.com') !== false || strpos($url, 'github.com') !== false) {
			if (!empty($this->github_token)) {
				$args['headers']['Authorization'] = 'Bearer ' . $this->github_token;
			}
        }
        return $args;
    }

	/**
	 * @param $plugin_file
	 * @return void
	 */
	private function init_action_filters($plugin_file): void
	{
		add_action('init', function () use ($plugin_file) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			$this->plugin_data = get_plugin_data($plugin_file, false, true);

			add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_updates']);
			add_filter('upgrader_pre_download', [$this, 'download_package'], 10, 3);
			add_filter('http_request_args', [$this, 'http_request_args'], 10, 2);
			add_filter(
				'plugins_api',
				[$this, 'get_plugin_details_report'],
				10,
				3
			);
		});
	}

	/**
	 * @return array|array[]
	 */
	public function get_http_request_options($options = []): array
	{
		if (!isset($options['headers'])) {
			$options['headers'] = [];
		}
		if (!isset($options['headers']['Accept'])) {
			$options['headers']['Accept'] = 'application/vnd.github.v3+json';
		}
		if (!isset($options['headers']['User-Agent'])) {
			$options['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version');
		}
		if (isset($this->github_token)) {
			$options['headers']['Authorization'] = 'Bearer ' . $this->github_token;
		}
		return $options;
	}
}
