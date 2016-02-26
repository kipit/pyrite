<?php

namespace Pyrite\KernelStack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class NewrelicMiddleware implements HttpKernelInterface, TerminableInterface
{
    /**
     * @var HttpKernelInterface
     */
    protected $app;

    /**
     * @var string
     */
    protected $applicationName;

    /**
     * @var \Intouch\Newrelic\Newrelic
     */
    protected $newRelic;

    /**
     * NewrelicMiddleware constructor.
     *
     * @param HttpKernelInterface $app
     * @param string              $applicationName
     */
    public function __construct(HttpKernelInterface $app, $applicationName)
    {
        $this->app = $app;
        $this->applicationName = $applicationName;

        $this->newRelic = new \Intouch\Newrelic\Newrelic(false);
        $this->newRelic->setAppName($applicationName);
    }

    /**
     * @param Request $request
     * @param int     $type
     * @param bool    $catch
     *
     * @return Response|void
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if($type === HttpKernelInterface::SUB_REQUEST){
            if($request->attributes->get('exception') instanceof \Exception){
                $e = $request->attributes->get('exception');
                $this->newRelic->noticeError($e->getMessage(), $e);
            }

            return $this->app->handle($request, $type, $catch);
        }

        $this->newRelic->addCustomParameter('url', $request->getPathInfo());
        $this->newRelic->addCustomParameter('content_type', $request->getContentType());

        $routeName = $request->attributes->get('_route');

        if(null !== $routeName){
            $routeName = explode('.', $routeName);
            $this->newRelic->nameTransaction($routeName[0]);
        }

        $this->newRelic->addCustomTracer('Pyrite\Kernel\PyriteKernel::run');

        $response = $this->app->handle($request, $type, $catch);

        $this->newRelic->addCustomParameter('result_code', $response->getStatusCode());

        return $response;
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->newRelic->endOfTransaction();

        if($this->app instanceof TerminableInterface){
            $this->app->terminate($request, $response);
        }
    }
}
