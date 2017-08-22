<?php
namespace Proto;

use UniDataBuffer\UniDataBuffer;

class ProtoTest{
	private $Socket;
	public function SetSocket($Socket_)
	{
		$this->Socket = $Socket_;		
	}
	
	public function RunSendTest()
	{
		printf("<br>Send test start<br>");
		
		$Proto01 = new Proto();
		$Proto01->SetDebug(true);
		$Proto01->SetCommand(0xF5);
		$Proto01->SetModule(0x21);
		$Proto01->SetSender(0xCECABEBA);
		$Proto01->SetReceiver(0xBBBBBBBB);
		$Proto01->SetSequence(0xEEEEEEEE);

		$AddData = new UniDataBuffer();
		$AddData->AppendDWord(0x11223344);
		$Proto01->SetDataContainer($AddData);
		
		$ResultBuffer = new UniDataBuffer();
		$Proto01->CompilePackage($ResultBuffer);
		$this->Socket->socket_send($ResultBuffer->GetBuffer());
		
		printf("<br>Send test end<br>");
	}
	public function RunReceiveTest()
	{
		printf("<br>Receive test start<br>");
		
		$ReceivedData = $this->Socket->socket_read(21);
		$IncomingBuffer = new UniDataBuffer();
		$IncomingBuffer->AppendAnyData($ReceivedData);
		
		$Proto01 = new Proto();
		$Proto01->SetDebug(true);
		
		$ParseResult = $Proto01->ParsePackage($IncomingBuffer);
		
		printf("<br>Receive test end<br>");
	}
}

