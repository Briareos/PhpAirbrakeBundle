<?php
namespace Nodrew\Bundle\PhpAirbrakeBundle\Airbrake;

use Airbrake\Client as AirbrakeClient;
use Airbrake\Notice;
use Airbrake\Configuration as AirbrakeConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The PhpAirbrakeBundle Client Loader.
 *
 * This class assists in the loading of the php-airbrake Client class.
 *
 * @package          Airbrake
 * @author           Drew Butler <hi@nodrew.com>
 * @copyright    (c) 2011 Drew Butler
 * @license          http://www.opensource.org/licenses/mit-license.php
 */
class Client extends AirbrakeClient
{

    protected $enabled = false;

    /**
     * @param string       $apiKey
     * @param RequestStack $requestStack
     * @param string       $projectRoot
     * @param string|null  $queue
     */
    public function __construct($apiKey, $envName, RequestStack $requestStack, $projectRoot, $queue = null, $apiEndPoint = null)
    {
        if (!$apiKey) {
            return;
        }

        $this->enabled = true;

        $projectRoot = realpath($projectRoot);

        if (!$request = $requestStack->getMasterRequest()) {
            $serverData  = "";
            $getData     = "";
            $postData    = "";
            $sessionData = null;
            $action      = 'None';
            $component   = 'None';
        } else {
            $controller = 'None';
            $action     = 'None';

            if ($sa = $request->attributes->get('_controller')) {
                $controllerArray = explode('::', $sa);
                if (sizeof($controllerArray) > 1) {
                    list($controller, $action) = $controllerArray;
                }
            }

            $serverData  = $request->server->all();
            $getData     = $request->query->all();
            $postData    = $request->request->all();
            $sessionData = ($request->hasSession() && $request->getSession()->isStarted()) ? $request->getSession()->all() : null;
            $component   = $controller;
        }
        $options = array(
            'environmentName' => $envName,
            'queue'           => $queue,
            'serverData'      => $serverData,
            'getData'         => $getData,
            'postData'        => $postData,
            'sessionData'     => $sessionData,
            'component'       => $component,
            'action'          => $action,
            'projectRoot'     => $projectRoot,
        );
        if (!empty($apiEndPoint)) {
            $options['apiEndPoint'] = $apiEndPoint;
        }
        parent::__construct(new AirbrakeConfiguration($apiKey, $options));
    }

    /**
     * Notify about the notice.
     *
     * If there is a PHP Resque client given in the configuration, then use that to queue up a job to
     * send this out later. This should help speed up operations.
     *
     * @param Airbrake\Notice $notice
     */
    public function notify(Notice $notice)
    {
        if ($this->enabled) {
            parent::notify($notice);
        }
    }
}
