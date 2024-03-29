<?php
namespace Minds\Core;

/**
 * Minds Page Controller
 * @todo Proper class capitalization
 */
class page extends base
{
    public $context = null;
    public $csrf = true;

    /**
     * Initializes the controller
     * @return null
     */
    public function init()
    {
        $this->setup();

        if ($this->csrf) {
            $this->checkCSRF();
        }
    }

    /**
     * TBD. Not implemented.
     * @return mixed
     */
    public function setup()
    {
    }

    /**
     * Performs a blocking check for CSRF attacks...
     *
     * No actions should use the GET method, instead all POST request, unless specifically stated via the $csrf attribute, will be featured.
     */
    public function checkCSRF()
    {
        if (!Session::isLoggedIn()) {
            return true;
        }

        if (empty($_POST)) {
            return true;
        }

        if (token::validate()) {
            return true;
        }

        //\register_error('Sorry, you failed the CSRF check');
        $this->forward(REFERRER);
    }

    /**
     * Render the page
     * @todo handle all pages
     * @param  array  $params - options to pass
     * @return string - the page.
     */
    public function render(array $params = [])
    {
        return "";
    }

    /**
     * Forward a page
     * @param  string $location - the url to move to
     * @param  string $reason   - the reason for the move
     * @return null
     */
    public function forward($location = "", $reason = 'system')
    {
        if (!headers_sent()) {
            if ($location === REFERER) {
                $location = $_SERVER['HTTP_REFERER'];
            }

            if ($location) {
                header("Location: {$location}");
                exit;
            } elseif ($location === '') {
                exit;
            }
        } else {
            throw new \SecurityException('SecurityException:ForwardFailedToRedirect');
        }
    }
}
