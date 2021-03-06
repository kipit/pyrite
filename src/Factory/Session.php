<?php

namespace Pyrite\Factory;

use Pyrite\Container\Container;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Session implements HttpKernelFactory
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function register(HttpKernelInterface $app = null, $name = '', array $parameters = array())
    {
        if (null === $app) {
            throw new \RuntimeException("Session must have a wrapped kernel");
        }

        $cookieParams = $this->container->getParameter('cookie', array());

        $start = false;
        if (array_key_exists('start', $parameters)) {
            if (is_scalar($parameters['start'])) {
                $start = (bool) $parameters['start'];
            }
        }

        return array($name, new \Pyrite\Stack\Session($app, $start, $cookieParams));
    }
}
