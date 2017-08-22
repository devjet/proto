<?php
namespace Proto;

use UniDataBuffer\UniDataBuffer;


/**
 * getters functions return default field value which are set in base constructor
 */
class Proto extends ProtoBase  {
	
	public function SetCommand($Cmd)
	{
		$this->m_bHaveCommand = 0x01;
		$this->m_btCommand = $Cmd & 0x000000FF;
	}
	public function GetCommand($Cmd)
	{
		return $this->m_btCommand;
	}
	public function SetModule($Mod)
	{
		$this->m_bHaveCommand = 0x01;
		$this->m_btModule = $Mod & 0x000000FF;
	}
	public function GetModule()
	{
		return $this->m_btModule;
	}	
	public function SetSender($Sender)
	{
		$this->m_dwSender = $Sender;
	}
	public function GetSender()
	{
		return $this->m_dwSender;
	}	
	public function SetReceiver($Receiver)
	{
		$this->m_dwReceiver = $Receiver;
	}
	public function GetReceiver()
	{
		return $this->m_dwReceiver;
	}	
	public function SetSequence($SID)
	{
		$this->m_dwSequenceID = $SID;
	}
	public function GetSequence()
	{
		return $this->m_dwSequenceID;
	}
	public function SetDataContainer(UniDataBuffer $dc)
	{
		$this->m_DataContainer = $dc;
	}
	public function GetDataContainer()
	{
		return $this->m_DataContainer;
	}
}