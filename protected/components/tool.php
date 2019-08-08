<?php

namespace Components;

use PanelAdmin\Components\simple_html_dom;

class Tool
{
    protected $_basePath;

    public function __construct($_basePath = null)
    {
        $this->_basePath = $_basePath;
    }
    
    public function get_css($data, $eregs = null)
    {
        if (!file_exists($this->_basePath . $data['path']))
            return false;
        $result = file_get_contents($this->_basePath . $data['path']);
        if ($result) {
            if ($eregs) {
                if (!is_array($eregs['patern'])) {
                    $pattern = $eregs['patern'];
                    $patterns = "/" . preg_replace(['/\//'], ['\/'], $pattern) . "/";
                    $replacements = $eregs['replacement'];
                    $result = preg_replace([$patterns], [$replacements], $result);
                } else {
                    $patterns = [];
                    foreach ($eregs['patern'] as $i => $pat) {
                        $new_pat = "/" . preg_replace(['/\//'], ['\/'], $pat) . "/";
                        $patterns[$i] = $new_pat;
                    }
                    $result = preg_replace($patterns, $eregs['replacement'], $result);
                }
            }
            
            return '<style>' . $result . '</style>';
        } else {
            return false;
        }
    }

    public function get_js($data)
    {
        if (!file_exists($this->_basePath . $data['path']))
            return false;
        $result = file_get_contents($this->_basePath . $data['path']);
        if ($result) {
            return '<script type="text/javascript">' . $result . '</script>';
        } else {
            return false;
        }

    }

    public function url_origin( $use_forwarded_host = false )
    {
        $s = $_SERVER;
        $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        $port     = $s['SERVER_PORT'];
        $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
        $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host;
    }

    public function get_sitemaps() {
        $results = [];
        $omodel = new \Model\OptionsModel();
        $pages = array();
        foreach (glob($_SERVER['DOCUMENT_ROOT'].'/themes/'.$omodel->getOptions()['theme'].'/views/*.phtml') as $filename) {
            $page = basename($filename, '.phtml');
            $excludes = ['post', 'sitemap.xml', '404'];
            if (file_exists($filename) && !in_array($page, $excludes) && strpos($page, "_") == false) {
                $loc = self::url_origin().'/'.$page;
                if ($page == 'index') {
                    $loc = self::url_origin().'/';
                }
                $pages[] = [
                    'loc' => $loc,
                    'lastmod' => date ("c", filemtime($filename)),
                    'priority' => ($page == 'index')? 1.0 : 0.5
                ];
            }
        }
        $results = array_merge($results, $pages);

        $exts = $omodel->getInstalledExtensions();

        if (array_key_exists("blog", $exts)) {
            $pmodel = new \ExtensionsModel\PostModel();
            $posts = $pmodel->getSitemaps();
            $results = array_merge($results, $posts);
        }

        if (array_key_exists("product", $exts)) {
            $pmodel = new \ExtensionsModel\ProductModel();
            $products = $pmodel->getSitemaps();
            $results = array_merge($results, $products);
        }

        return $results;
    }

    public function getConfigs($option_name = null) {
        if (!file_exists(realpath(dirname(__DIR__)).'/data/configs.json'))
            return [];

        $content = file_get_contents(realpath(dirname(__DIR__)).'/data/configs.json');
        $cfgs = json_decode($content, true);

        if (!empty($option_name)) {
            if (is_array($cfgs) && array_key_exists($option_name, $cfgs)){
                return $cfgs[$option_name];
            } else {
                return null;
            }
        }

        return $cfgs;
    }

    public function translate($text) {
        $current_lang = self::getConfigs('language');
        if (isset($_COOKIE['lang'])) {
            $current_lang = $_COOKIE['lang'];
        }
        $lang = 'en';
        if (!empty($current_lang)) {
            $lang = $current_lang;
        }

        /*$lmodel = \ExtensionsModel\PostLanguageModel::model()->findByAttributes(['code' => $lang]);
        $model = new \ExtensionsModel\TranslationModel();
        $txt = $model->findByOriginalText(['language_id' => $lmodel->id, 'original_text' => $text]);
        return $txt;*/

        if (!file_exists(realpath(dirname(__DIR__)).'/data/trans_'.$lang.'.json'))
            return $text.'_df';

        $content = file_get_contents(realpath(dirname(__DIR__)).'/data/trans_'.$lang.'.json');
        $translations = json_decode($content, true);

        if (is_array($translations) && array_key_exists($text, $translations)){
            return $translations[$text];
        }

        return $text;
    }

    public function get_rss($data = []) {
        $fname = realpath(dirname(__DIR__)).'/data/rss_feeds.json';
        if (file_exists(realpath(dirname(__DIR__)).'/data/rss_feeds.json')) {
            $content = file_get_contents(realpath(dirname(__DIR__)).'/data/rss_feeds.json');
            $channel = json_decode($content, true);
            $expired_time = filemtime($fname) + (3600 * 6);
            //$expired_time = filemtime($fname) + 60;
            if (!empty($channel) && !isset($data['reload'])) {
                if (isset($data['id'])) {
                    return $channel['item'][$data['id']];
                }

                return $channel;
            }
        }

        $omodel = new \Model\OptionsModel();
        $options = $omodel->getOptions();
        if (in_array("rss_url", array_keys($options))) {
            $result = [];
            try {
                /*$xml = simplexml_load_file($options['rss_url'], null, LIBXML_NOCDATA);
                $result = $xml->channel;*/
                // using curl instead
                $rss_data = $this->url_get_contents($options['rss_url']);
                if (!empty($rss_data)) {
                    $rss_data = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $rss_data);
                }
                $xml = simplexml_load_string($rss_data, null, LIBXML_NOCDATA);
                $result = $xml->channel;
            } catch (\Exception $e){var_dump($e->getMessage());}

            if (!empty($result)) {
                try {
                    file_put_contents($fname, json_encode($result));
                } catch (Exception $e) {}
                return $result;
            }
        }

        return false;
    }

    public function url_get_contents ($Url) {

        if (!function_exists('curl_init')){
            die('CURL is not installed!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "SomeUserAgent");
        curl_setopt($ch, CURLOPT_COOKIE, 'AspxAutoDetectCookieSupport=1');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function rpHash($value) {
        $hash = 5381;
        $value = strtoupper($value);
        for($i = 0; $i < strlen($value); $i++) {
            $hash = ($this->leftShift32($hash, 5) + $hash) + ord(substr($value, $i));
        }
        return $hash;
    }

    private function leftShift32($number, $steps) {
        // convert to binary (string)
        $binary = decbin($number);
        // left-pad with 0's if necessary
        $binary = str_pad($binary, 32, "0", STR_PAD_LEFT);
        // left shift manually
        $binary = $binary.str_repeat("0", $steps);
        // get the last 32 bits
        $binary = substr($binary, strlen($binary) - 32);
        // if it's a positive number return it
        // otherwise return the 2's complement
        return ($binary{0} == "0" ? bindec($binary) :
            -(pow(2, 31) - bindec(substr($binary, 1))));
    }
}