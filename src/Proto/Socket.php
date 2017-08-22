<?php
namespace Proto;

/**
 * Socket handle
 * socket wrapper
 */
class Socket {
	
	public $SOCKET;

	function __construct(){
		$this->P_socket_init();
	}

	function __destruct(){
		socket_close($this->SOCKET);
	}

	private function P_socket_init(){
		if($this->SOCKET === null){
			$this->P_socket_create();
		}else return ;
	}

	private function P_socket_create(){
		return $this->SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	}

	public function socket_connect($host,$port){
		$this->P_socket_init();
		$bool = @socket_connect($this->SOCKET, $host, $port);
	
		if(!$bool){
			echo("socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n");
                        die;
		}
		
		return $bool;
	}
	
	public function socket_strerror(){	
		return socket_strerror(socket_last_error($this->SOCKET));
	}
	
	public function socket_send($buf){
		return @socket_send($this->SOCKET,$buf,strlen($buf),0);
	}
	public function socket_read($amount){
		return @socket_read($this->SOCKET, $amount, PHP_BINARY_READ );
	}	
}