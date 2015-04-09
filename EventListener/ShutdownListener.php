<?php
namespace Nodrew\Bundle\PhpAirbrakeBundle\EventListener;

use Nodrew\Bundle\PhpAirbrakeBundle\Airbrake\Client,
    Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * The PhpAirbrakeBundle ShutdownListener.
 *
 * Handles shutdown errors and make sure they get logged.
 *
 * @package          Airbrake
 * @author           Drew Butler <hi@nodrew.com>
 * @copyright    (c) 2012 Drew Butler
 * @license          http://www.opensource.org/licenses/mit-license.php
 */
class ShutdownListener
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Register the handler on the request.
     *
     * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function register(FilterControllerEvent $event)
    {
        register_shutdown_function(array($this, 'onShutdown'));
    }

    /**
     * Handles the PHP shutdown event.
     *
     * This event exists almost solely to provide a means to catch and log errors that might have been
     * otherwise lost when PHP decided to die unexpectedly.
     */
    public function onShutdown()
    {
        // Get the last error if there was one, if not, let's get out of here.
        if (!$error = error_get_last()) {
            return;
        }

        $fatalErrors = array(
            E_ERROR             => 'E_ERROR',
            E_PARSE             => 'E_PARSE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_USER_ERROR        => 'E_USER_ERROR',
        );

        if (!isset($fatalErrors[$error['type']])) {
            return;
        }

        $errorName = $fatalErrors[$error['type']];

        $message   = sprintf('[%s]: %s', $errorName, $error['message']);
        $backtrace = array(array('file' => $error['file'], 'line' => $error['line']));

        $this->client->notifyOnError($message, $backtrace);
    }
}
