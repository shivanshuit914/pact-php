<?php namespace Pact;

class MockService {

  function __construct($args) {
    if (!array_key_exists('port', $args) || !$args['port']) {
      throw new \InvalidArgumentException('Error creating MockService. Please provide the Pact mock service port');
    }
    if (array_key_exists('host', $args)) {
      $this->host = $args['host'];
    }
    $this->baseURL = 'http://' . $this->host . ':' . $args['port'];
    if (!array_key_exists('consumer', $args)) {
      throw new \InvalidArgumentException('Error creating MockService. Please provide the Pact consumer name');
    }
    if (!array_key_exists('provider', $args)) {
      throw new \InvalidArgumentException('Error creating MockService. Please provide the Pact provider name');
    }
    $this->pactDetails = ['consumer' => ['name' => $args['consumer']], 'provider' => ['name' => $args['provider']]];
    if (array_key_exists('pactfile_write_mode', $args) && ($args['pactfile_write_mode'] == 'update' || $args['pactfile_write_mode'] == 'overwrite')) {
      $this->pactDetails['pactfile_write_mode'] = $args['pactfile_write_mode'];
    }
  }

  private $host = '127.0.0.1';
  private $port;
  private $pactDetails = [];
  private $interactions = [];
  private $baseURL;

  public function given($providerState) {
    $interaction = new Interaction();
    $interaction->given($providerState);
    array_push($this->interactions, $interaction);
    return $interaction;
  }

  public function uponReceiving($description) {
    $interaction = new Interaction();
    $interaction->uponReceiving($description);
    array_push($this->interactions, $interaction);
    return $interaction;
  }

  private function setup() {
    $interactions = $this->interactions;
    $this->interactions = [];
    $mockServiceRequests = new MockServiceRequests();
    $mockServiceRequests->putInteractions($interactions, $this->baseURL);
  }

  private function verifyAndWrite() {
    $this->verify();
    $this->write();
  }

  private function verify() {
    $mockServiceRequests = new MockServiceRequests();
    $mockServiceRequests->getVerification($this->baseURL);
  }

  private function write() {
    $mockServiceRequests = new MockServiceRequests();
    $mockServiceRequests->postPact($this->pactDetails, $this->baseURL);
  }

  public function run($testMethod) {
    if (!is_callable($testMethod)) {
      throw new \InvalidArgumentException('Error running MockService. Please provide a callable test method.');
    }
    $this->setup();
    $testMethod();
    $this->verifyAndWrite();
  } 

}
