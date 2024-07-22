<?php namespace Onigoetz\ImagecacheUtils\Support;

use Slim\App;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WebTestClient
{
    /** @var \Slim\App */
    public $app;

    /** @var  \Slim\Http\Request */
    public $request;

    /** @var  \Slim\Http\Response */
    public $response;

    private $cookies = [];

    public function __construct(App $slim)
    {
        $this->app = $slim;
    }

    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException(strtoupper($method) . ' is not supported');
    }

    public function get($path, $data = [], $optionalHeaders = [])
    {
        return $this->request('get', $path, $data, $optionalHeaders);
    }

    // Abstract way to make a request to SlimPHP, this allows us to mock the
    // slim environment
    private function request($method, $path, $data = [], $optionalHeaders = [])
    {
        $uri = new Uri($path);

        $this->request = new ServerRequest($method, $uri);

        // Process request
        $app = $this->app;
        $this->response = $app->handle($this->request);

        // Return the application output.
        return (string)$this->response->getBody();
    }

    public function setCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }
}
