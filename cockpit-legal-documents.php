<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Twig\TwigFunction;
/**
 * Class CockpitLegalDocumentsPlugin
 * @package Grav\Plugin
 */
class CockpitLegalDocumentsPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            'onTwigInitialized' => ['onTwigInitialized', 0]
        ]);
    }

    // Add Twig Extensions.
    public function onTwigInitialized() {
        $this->grav['twig']->twig->addFunction(new TwigFunction('privacy_statement', [$this, 'privacy'], ['is_safe' => ['html']]));
        $this->grav['twig']->twig->addFunction(new TwigFunction('imprint', [$this, 'imprint'], ['is_safe' => ['html']]));
    }

    public function imprint($lang='de'){
        return $this->getDocuments('imprint',$lang);
    }

    public function privacy($lang='de'){
        return $this->getDocuments('privacy',$lang);
    }

    public function getDocuments($type, $lang){
        $key = $this->config->get('plugins.cockpit-legal-documents.'.$type.'.key');
        if($key == "") return false;
        return $content = $this->getCachedData($key,$lang);
    }

    public function getCachedData($key, $lang){
        if($this->config->get('plugins.cockpit-legal-documents.settings.enableCache') == false){
            return $this->getHtmlFromApi($key,$lang);
        }

        $cache = $this->grav['cache'];
        $cache->setEnabled(true); //enable caching even when its turned off in settings for page or system
        
        $id = 'cockpit-legal-documents'.'.'.$key.'.'.$lang;

        $duration = $this->config->get('plugins.cockpit-legal-documents.settings.cacheDuration')*60*24;

        if ($data = $cache->fetch($id)) {
            return $data;
        } else {
            $data = $this->getHtmlFromApi($key,$lang);
            $cache->save($id, $data, $duration);
            return $data;
        }

    }


    public function getHtmlFromApi($key,$lang="de"){

        $url = $this->config->get('plugins.cockpit-legal-documents.settings.serverUrl') . '/'.$key.'/document/render/html?language='.$lang;
        
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
          return false;
        }

        $htmlContent = file_get_contents($url);
        $htmlContent .= '<div style="display:none;">Abgerufen vom Server am '.date('d.m.Y H:i:s').'</div>';
        return $htmlContent;
    }
}
