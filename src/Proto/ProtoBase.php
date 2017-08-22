<?php
namespace Proto;

use UniDataBuffer\UniDataBuffer;
use UniBit\UniBit;

define('PACKET_FLAG2_CRC_MASK',0x7f);

class ProtoBase {

	private   $bDebugOn = false;
	
	protected $m_dwSender;
	protected $m_dwReceiver;
	protected $m_btModule;
	protected $m_btCommand;
	protected $m_dwSequenceID;
	protected $m_dwUnpackedLength;
	protected $m_bHaveCommand;
	protected $m_DataContainer;
	
	protected $m_bCRC;

	function __construct(){
		$this->m_dwSender 	= false;
		$this->m_dwReceiver 	= false;
		$this->m_btModule 	= false;
		$this->m_btCommand 	= false;
		$this->m_dwSequenceID 	= false;
		$this->m_dwUnpackedLength = 0;
		$this->m_bHaveCommand 	= false;
		$this->bCRC = 0;
		$this->m_DataContainer = null;
	}		

	/**
  * Calculating packet CRC
  *
  * @param string $pPacket
  * @param int $dwPacketSize
  * @return unknown
  */
	public function CalcCRC($pPacket,$dwPacketSize)
	{
		if ( is_null ( $pPacket ) || ($dwPacketSize < 2))
		return 0;

		$btCRC = ord($pPacket{0});
		$btCRC ^= (ord($pPacket{1}) & (~PACKET_FLAG2_CRC_MASK));
		while ($dwPacketSize !=2)
		$btCRC ^= ord($pPacket{--$dwPacketSize});

		$btTail = $btCRC & (~PACKET_FLAG2_CRC_MASK);
		
		$btMask = 255 - PACKET_FLAG2_CRC_MASK;

		while ($btMask > PACKET_FLAG2_CRC_MASK){
			$btTail >>= 1;  $btMask >>=1;
		}

		$btCRC ^= $btTail;

		return $btCRC & PACKET_FLAG2_CRC_MASK;

	}


	/**
     * Parse an incomming or outgoing package
     *
     * @param UniDataBuffer $inBuffer
     * @return int size
     */
	public function ParsePackage(UniDataBuffer& $inBuffer){

		$inBuffer->ResetSeek();
		
		if( $inBuffer->GetSize() < 2 ){
			return 0;  // no flags
		}
		$dwParsedSize = 2;

		$btFlag1 = $inBuffer->GetAt(0);
		$this->DebugPrint(sprintf("FLAG1: %02X",$btFlag1));
		$btFlag2 = $inBuffer->GetAt(1);
		$this->DebugPrint(sprintf("FLAG2: %02X",$btFlag2));
		

		$dwHeaderSize = 2;


		if( UniBit::IsBitSet($btFlag1,0) ){
			$dwHeaderSize +=4; // have sender
		}
		if( UniBit::IsBitSet($btFlag1,1)){
			$dwHeaderSize +=4;  // have receiver
		}
		$bDataLen = 0;
		if( UniBit::IsBitSet($btFlag1,2) && ! UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 1;
		}
		if( ! UniBit::IsBitSet($btFlag1,2) &&  UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 2;
		}
		if( UniBit::IsBitSet($btFlag1,2) &&  UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 4;
		}
		$dwHeaderSize += $bDataLen;

		if( UniBit::IsBitSet($btFlag1,4)){
			$dwHeaderSize += 2;  // module and command
		}
		if( UniBit::IsBitSet($btFlag1,5)){
			$dwHeaderSize += 4;  // sequence
		}
		if( UniBit::IsBitSet($btFlag1,6)){
			$dwHeaderSize += $bDataLen;  // packed by bzip data
		}
		$inbuffsize = $inBuffer->GetSize();
		if( $dwHeaderSize > $inbuffsize){
			return 0; // need more data
		}
		// have all header, will try to parse fields

		$inBuffer->SkipRead(2);

		if( UniBit::IsBitSet($btFlag1,0)){ //check for sender
			$m_dwSender = $inBuffer->ReadDWord();  
			$this->DebugPrint(sprintf("m_dwSender: %08X",$m_dwSender));
			$this->m_dwSender = $m_dwSender;
		}

		if( UniBit::IsBitSet($btFlag1,1)){ // check for receiver
			$m_dwReceiver = $inBuffer->ReadDWord(); 
			$this->DebugPrint(sprintf("m_dwReceiver: %08X",$m_dwReceiver));
			$this->m_dwReceiver = $m_dwReceiver;
		}

		if( UniBit::IsBitSet($btFlag1,4)) { // module and command
			$m_btModule = $inBuffer->ReadByte();
			$this->DebugPrint(sprintf("m_btModule: %02X",$m_btModule));
			$this->m_btModule = $m_btModule;
			$m_btCommand = $inBuffer->ReadByte();
			$this->DebugPrint(sprintf("m_btCommand: %02X",$m_btCommand));
			$this->m_btCommand = $m_btCommand;
			$this->m_bHaveCommand  = true;
		}

		if( UniBit::IsBitSet($btFlag1,5)) { // sequence
			$m_dwSequenceID = $inBuffer->ReadDWord(); 
			$this->DebugPrint(sprintf("m_dwSequenceID: %08X",$m_dwSequenceID));
			$this->m_dwSequenceID = $m_dwSequenceID;
		}

		// data

		$dwDataLength = 0;


		if( $bDataLen == 1){
			$dwDataLength = $inBuffer->ReadByte();
			if(UniBit::IsBitSet($btFlag1,6)){
				$m_dwUnpackedLength = $inBuffer->ReadByte();
				$this->m_dwUnpackedLength  =  $m_dwUnpackedLength;
			}
		}

		if( $bDataLen == 2){
			$dwDataLength = $inBuffer->ReadWord(); 
			if(UniBit::IsBitSet($btFlag1,6)){ 
				$m_dwUnpackedLength = $inBuffer->ReadWord();  
				$this->m_dwUnpackedLength = $m_dwUnpackedLength;
			}
		}

		if ($bDataLen == 4) {
			$dwDataLength = $inBuffer->ReadDWord(); 
			if(UniBit::IsBitSet($btFlag1,6)){  
				$m_dwUnpackedLength = $inBuffer->ReadDWord();  
				$this->m_dwUnpackedLength = $m_dwUnpackedLength;
			}
		}

		//header parsed   


		if($inBuffer->GetSize()< $dwDataLength + $dwHeaderSize) {
			return 0;
		}

		$this->m_bCRC = $this->CalcCRC($inBuffer->GetBuffer(),$inBuffer->GetSize());
		if( $btFlag2 != $this->m_bCRC ) {
			$this->DebugPrint(sprintf("CRC does not match")); 
			return -1;
		}  // -1 CRC Error
		else {
			$this->DebugPrint(sprintf("CRC OK"));
		}
		
		//additional data
		if($dwDataLength > 0)
		{
			$this->m_DataContainer = new UniDataBuffer();
			$data = $inBuffer->ReadData($dwDataLength);
			$this->m_DataContainer->AppendAnyData($data);
		}
		
		return $dwHeaderSize + $dwDataLength;
	}

	public function GetIncompleteLength(UniDataBuffer& $inBuffer){

		$inBuffer->ResetSeek();
		
		if( $inBuffer->GetSize() < 2 ){
			return 2 - $inBuffer->GetSize();  // no flags
		}
		$dwParsedSize = 2;
		$btFlag1 = $inBuffer->GetAt(0);
		$btFlag2 = $inBuffer->GetAt(1);
		//TODO -- addional flags //
		$dwHeaderSize = 2;


		if( UniBit::IsBitSet($btFlag1,0) ){
			$dwHeaderSize +=4; // have sender
		}
		if( UniBit::IsBitSet($btFlag1,1)){
			$dwHeaderSize +=4;  // have receiver
		}
		$bDataLen = 0;
		if( UniBit::IsBitSet($btFlag1,2) && ! UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 1;
		}
		if( ! UniBit::IsBitSet($btFlag1,2) &&  UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 2;
		}
		if( UniBit::IsBitSet($btFlag1,2) &&  UniBit::IsBitSet($btFlag1,3) ){
			$bDataLen = 4;
		}
		$dwHeaderSize += $bDataLen;

		if( UniBit::IsBitSet($btFlag1,4)){
			$dwHeaderSize += 2;  // module and command
		}
		if( UniBit::IsBitSet($btFlag1,5)){
			$dwHeaderSize += 4;  // sequence
		}
		if( UniBit::IsBitSet($btFlag1,6)){
			$dwHeaderSize += $bDataLen;  // packed by bzip data
		}
		$inbuffsize = $inBuffer->GetSize();
		
		if( $dwHeaderSize > $inbuffsize){
			return $dwHeaderSize - $inbuffsize;
		}
		// have all header, will try to parse fields
		
		
		$inBuffer->SkipRead(2);
		//TODO -- addional flags //
		
		$dwWord = 0;

		if( UniBit::IsBitSet($btFlag1,0)){ //check for sender
			$dwWord = $inBuffer->ReadDWord();
		}

		if( UniBit::IsBitSet($btFlag1,1)){ // check for receiver
			$dwWord = $inBuffer->ReadDWord(); 
		}
		
		$btTmp = 0;
		if( UniBit::IsBitSet($btFlag1,4)) { // module and command
			$btTmp = $inBuffer->ReadByte();
			$btTmp = $inBuffer->ReadByte();
		}

		if( UniBit::IsBitSet($btFlag1,5)) { // sequence
			$dwWord = $inBuffer->ReadDWord(); 
		}

		// data

		$dwWord = 0;


		if( $bDataLen == 1){
			$dwWord = $inBuffer->ReadByte();
		}

		if( $bDataLen == 2){
			$dwWord = $inBuffer->ReadWord();
		}

		if ($bDataLen == 4) {
			$dwWord = $inBuffer->ReadDWord(); 
		}


		if($inBuffer->GetSize() < $dwHeaderSize + $dwWord) {
			return $dwHeaderSize + $dwWord - $inBuffer->GetSize();
		}

		return 0;		
		
	}
	
	
	
	/**
	 * Compile a request package
	 *
	 * @param UniDataBuffer $outBuffer
	 * @return int outBuffer size
	 */
	public function CompilePackage(UniDataBuffer &$outBuffer){
		$btFlag1 =0x00; //0 // 000 000 00
		$btFlag2 =0x00; //0 // 000 000 00

		$outBuffer->AppendWord(0x0000); // place flag1 + flag2


		if($this->m_dwSender != 0){
			UniBit::BitOn($btFlag1,0);
			$outBuffer->AppendDWord($this->m_dwSender);
		}

		if($this->m_dwReceiver != 0){
			UniBit::BitOn($btFlag1,1);
			$outBuffer->AppendDWord($this->m_dwReceiver);
		}

		if( $this->m_bHaveCommand ){
			UniBit::BitOn($btFlag1,4);


			$outBuffer->AppendByte($this->m_btModule);
			$outBuffer->AppendByte($this->m_btCommand);
		}

		if( $this->m_dwSequenceID ){
			UniBit::BitOn($btFlag1,5);
			$outBuffer->AppendDWord($this->m_dwSequenceID);
		}



		if(!is_null($this->m_DataContainer)){
			$dwSize = $this->m_DataContainer->GetSize();
			if ( $dwSize ){

				if($dwSize < 256){
					UniBit::BitOn($btFlag1,2);
					$outBuffer->AppendByte($dwSize);
					if( $this->m_dwUnpackedLength )
					$outBuffer->AppendByte($this->m_dwUnpackedLength);
				}
				if($dwSize >= 256 && $dwSize <= 65535){
					UniBit::BitOn($btFlag1,3);
					$outBuffer->AppendWord($dwSize);
					if ( $this->m_dwUnpackedLength )
					$outBuffer->AppendWord($this->m_dwUnpackedLength);
				}
				if( $dwSize > 65535 ){
					UniBit::BitOn($btFlag1,2);
					UniBit::BitOn($btFlag1,3);
					$outBuffer->AppendDWord($dwSize);
					if ( $this->m_dwUnpackedLength )
					$outBuffer->AppendDWord($this->m_dwUnpackedLength);
				}
				if( $this->m_dwUnpackedLength ){
					UniBit::BitOn($btFlag1,6);
				}

				$outBuffer->AppendBuffer($this->m_DataContainer);
			}
		}


		$outBuffer->SetAt(0,$btFlag1);
		$outBuffer->SetAt(1,$btFlag2);

		$this->DebugPrint(sprintf("Compiled package size: %s",$outBuffer->GetSize()));

		$this->m_bCRC = $this->CalcCRC($outBuffer->GetBuffer(),$outBuffer->GetSize());
		$outBuffer->SetAt(1,$this->m_bCRC);


		return $outBuffer->GetSize();
	}
	
	public function SetDebug($State)
	{
		//Note. php construction to preserve boolean type in $this->bDebugOn....
		if( $State === true )
		{
			$this->bDebugOn = true;
		}
		else 
		{
			$this->bDebugOn = false;
		}
	}
	
	private function DebugPrint($Str)
	{
		if( $this->bDebugOn === true )
		{
			printf($Str."<br>");
		}
	}
}
