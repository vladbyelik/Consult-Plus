<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\blazy\BlazyMedia;

/**
 * A Trait common for Media integration with field details.
 *
 * No need to import this, if using just vanilla option.
 */
trait BlazyVideoTrait {

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * This should only be called for type video as file image has all
   * the needed info to get the image from.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return array
   *   The array of image item and settings if a file image, else empty.
   *
   * @todo merge it into self::getMediaItem()
   */
  public function getImageItem($file) {
    if ($item = BlazyMedia::imageItem($file)) {
      $settings         = (array) $item;
      $settings['type'] = 'image';
      $data['item']     = $item;
      $data['settings'] = $settings;

      return $data;
    }

    return [];
  }

  /**
   * Gets the Media item thumbnail, or re-associate the file entity to ME.
   *
   * @param array $data
   *   An array of data containing settings, and potential video thumbnail item.
   * @param object $media
   *   The Media file entity.
   */
  public function getMediaItem(array &$data, $media = NULL) {
    if ($this->targetType != 'file') {
      return;
    }

    $settings              = $data['settings'];
    $settings['media_url'] = entity_uri($this->targetType, $media)['path'];
    $settings['media_id']  = $media->fid;
    $settings['type']      = $media->type;
    $settings['media_uri'] = $media->uri;
    $settings['view_mode'] = empty($settings['view_mode']) ? 'default' : $settings['view_mode'];

    list($settings['scheme']) = array_pad(array_map('trim', explode(":", $media->uri, 2)), 2, NULL);

    // Ensures disabling Media sub-modules while being used doesn't screw up.
    try {
      $wrapper = file_stream_wrapper_get_instance_by_uri($media->uri);
      // No need for checking MediaReadOnlyStreamWrapper.
      if (!is_object($wrapper)) {
        throw new \Exception('Unable to find matching wrapper!');
      }

      $parts = $wrapper->get_parameters();

      // Wait! We got no way to fetch embed url from the media API?
      $settings['input_url']    = drupal_strip_dangerous_protocols($wrapper->interpolateUrl());
      $settings['embed_url']    = $this->getVideoEmbedUrl($settings['input_url']);
      $settings['autoplay_url'] = $this->getAutoplayUrl($settings['embed_url']);
      $settings['video_id']     = isset($parts['v']) ? check_plain($parts['v']) : '';
      $settings['uri']          = $wrapper->getLocalThumbnailPath();
      $settings['image_url']    = file_create_url($settings['uri']);
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Collect what's needed for clarity.
    $data['settings'] = $settings;
  }

  /**
   * Returns Youtube/ Vimeo video ID from URL, thanks to Kus from s.o.
   */
  public function getVideoId($url) {
    $parts = parse_url($url);
    if (isset($parts['query'])) {
      parse_str($parts['query'], $qs);
      if (isset($qs['v'])) {
        return $qs['v'];
      }
      elseif (isset($qs['vi'])) {
        return $qs['vi'];
      }
    }
    if (isset($parts['path'])) {
      $path = explode('/', trim($parts['path'], '/'));
      return $path[count($path) - 1];
    }
    return FALSE;
  }

  /**
   * Returns the host for scheme.
   */
  public function getHost($url) {
    $host = preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST));
    $host = explode(".", $host);
    return $host[0];
  }

  /**
   * Returns video thumbnail based on video id, needed by BlazyFilter.
   */
  public function getVideoThumbnail($url) {
    $vid = $this->getVideoId($url);
    if (!$vid) {
      return '';
    }

    // @todo use VEF or Media family functions instead if any.
    // @see file_uri_to_object($uri, $use_existing = TRUE)
    $dir = 'public://video_thumbnails';
    // @todo avoid hard-coded extension instead of using pathinfo().
    $destination = $dir . '/' . $vid . ".jpg";

    // Returns local file if already stored locally.
    if (is_file($destination)) {
      return file_create_url($destination);
    }

    // Download video thumbnail.
    $file_url = '';
    try {
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
      if (strpos($url, 'vimeo') !== FALSE) {
        // Supress useless warning for local environment without internet.
        $data = @file_get_contents("{$protocol}://vimeo.com/api/v2/video/{$vid}.json");
        $data = drupal_json_decode($data);
        $file_url = isset($data[0]) ? $data[0]->thumbnail_large : '';
      }
      elseif (strpos($url, 'youtu') !== FALSE) {
        $context_options = [
          "ssl" => [
            "verify_peer" => FALSE,
            "verify_peer_name" => FALSE,
          ],
        ];

        // Supress useless warning for local environment without internet.
        if (@file_get_contents("{$protocol}://img.youtube.com/vi/{$vid}/maxresdefault.jpg", 0, stream_context_create($context_options), 0, 1)) {
          $file_url = "{$protocol}://img.youtube.com/vi/{$vid}/maxresdefault.jpg";
        }
        elseif (@file_get_contents($protocol . "://img.youtube.com/vi/{$vid}/0.jpg", 0, stream_context_create($context_options), 0, 1)) {
          $file_url = "{$protocol}://img.youtube.com/vi/{$vid}/0.jpg";
        }
      }
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Cached remote thumbnail locally.
    if ($file_url) {
      if (!is_file($dir)) {
        file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
      }

      $file_uri = system_retrieve_file($file_url, $destination, FALSE, FILE_EXISTS_REPLACE);
      if ($file_uri) {
        $file_url = file_create_url($file_uri);
      }
    }

    return $file_url;
  }

  /**
   * Returns Youtube/ Vimeo video thumbnail based on video id.
   */
  public function getVideoEmbedUrl($url) {
    if ($vid = $this->getVideoId($url)) {
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
      if (strpos($url, 'youtu') !== FALSE) {
        return $protocol . '://www.youtube.com/embed/' . $vid;
      }
      elseif (strpos($url, 'vimeo') !== FALSE) {
        return $protocol . '://player.vimeo.com/video/' . $vid;
      }
    }
    return '';
  }

  /**
   * Returns Youtube/ Vimeo video thumbnail based on video id.
   */
  public function getAutoplayUrl($url) {
    $url = strpos($url, 'embed') !== FALSE || strpos($url, 'player') !== FALSE ? $url : $this->getVideoEmbedUrl($url);
    // Adds autoplay for media URL on lightboxes, saving another click.
    if (strpos($url, 'autoplay') === FALSE || strpos($url, 'autoplay=0') !== FALSE) {
      return strpos($url, '?') === FALSE ? $url . '?autoplay=1' : $url . '&autoplay=1';
    }

    return $url;
  }

}
