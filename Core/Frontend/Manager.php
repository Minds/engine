<?php
/**
 * Serve frontend
 */
namespace Minds\Core\Frontend;

use Minds\Core\Session;
use Minds\Core\Config;
use Minds\Core\Navigation;
use Minds\Core\Di\Di;
use Minds\Helpers;

class Manager
{
    /** @var $socket */
    private $socket;
    
    /** @var string $host */
    private $host = 'localhost';

    /** @var int $port */
    private $port = 9090;

    /**
     * Server the request
     * @param Request $request
     * @return string
     */
    public function serve($request)
    {
        $uri = $request->getRequestTarget();

        if (!Session::isLoggedIn()) { // Only do SSR for logged out
            try {
                return $this->renderServerSide($uri);
            } catch (\Exception $e) {
            }
        }

        return $this->renderClientSide();
    }

    /**
     * Render server side
     * @param string $uri
     * @return string
     */
    private function renderServerSide($uri)
    {
        // Temp override memory
        ini_set('memory_limit', '600M');

        // Create the socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new \Exception('Socket could not be created');
        }

        // Connect to the socket
        socket_connect($this->socket, $this->host, $this->port);

        $id = round(microtime(true)*1000); // May not need this?
        $send = json_encode([
                    'id' =>  $id,
                    'url' => $uri,
                    'document' => $this->getIndexFile(),
                ]);

        // Send the request
        socket_write($this->socket, $send, strlen($send));
        
        // Read the result
        $buffer = socket_read($this->socket, (1024 * 1024) * 200);

        // Decode JSON
        $response = json_decode($buffer, true);

        // Close socket
        socket_close($this->socket);

        return $response['html'];
    }

    /**
     * Render client side
     * TODO: resupport i18n
     * @return string
     */
    private function renderClientSide()
    {
        return $this->getIndexFile();
    }

    /**
     * Collect the index file
     * @return string
     */
    private function getIndexFile()
    {
        $dist = realpath(__MINDS_ROOT__ . '/../front/dist');
        $document  = file_get_contents($dist . '/en/index.php');
        $document = str_replace('<!-- MINDS_GLOBALS -->', json_encode($this->getWindowVariables()), $document);
        return $document;
    }

    /**
     * Return window variables
     * @return array
     */
    protected function getWindowVariables()
    {
          $minds = [
              "MindsContext" => 'app',
              "LoggedIn" => Session::isLoggedIn() ? true : false,
              "Admin" => Session::isAdmin() ? true : false,
              "cdn_url" => Config::_()->get('cdn_url') ?: Config::_()->cdn_url,
              "cdn_assets_url" => Config::_()->get('cdn_assets_url'),
              "site_url" => Config::_()->get('site_url') ?: Config::_()->site_url,
              "cinemr_url" => Config::_()->get('cinemr_url') ?: Config::_()->cinemr_url,
              "socket_server" => Config::_()->get('sockets-server-uri') ?: 'ha-socket-io-us-east-1.minds.com:3030',
              "navigation" => Navigation\Manager::export(),
              "thirdpartynetworks" => Di::_()->get('ThirdPartyNetworks\Manager')->availableNetworks(),
              'language' => Di::_()->get('I18n')->getLanguage(),
              'languages' => Di::_()->get('I18n')->getLanguages(),
              "categories" => Config::_()->get('categories') ?: [],
              "stripe_key" => Config::_()->get('payments')['stripe']['public_key'],
              "recaptchaKey" => Config::_()->get('google')['recaptcha']['site_key'],
              "max_video_length" => Config::_()->get('max_video_length'),
              "features" => (object) (Config::_()->get('features') ?: []),
              "blockchain" => (object) Di::_()->get('Blockchain\Manager')->getPublicSettings(),
              "sale" => Config::_()->get('blockchain')['sale'],
              "last_tos_update" => Config::_()->get('last_tos_update') ?: time(),
              "tags" => Config::_()->get('tags') ?: []
          ];

        if (Session::isLoggedIn()) {
            $minds['user'] = Session::getLoggedinUser()->export();
            $minds['user']['rewards'] = !!Session::getLoggedinUser()->getPhoneNumberHash();
            $minds['wallet'] = array('balance' => Helpers\Counters::get(Session::getLoggedinUser()->guid, 'points', false));
        }

        return $minds;
    }

}
