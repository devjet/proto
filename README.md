PROTO Data tranfer protocol
===========================

Similar to RPC protocol but dedicated to data transfer.
Was created to transfer huge amount of data and commands between platforms.
Utilizes PHP socket.


Protocol details
-------------

Stream line
[2-N:Flags][0/4:Sender][0/4:Reciever][1:Module][1:Command][ 0/4:Sequence_id][0-4:Additional data length][ 0-4:Unpacked data length][N:additional data, if lenght != null]



### Flags description


   |     | 0  | 1  |
   |:--:|:--:|
  |**State bit OFF** |has no recipient | has no sender| 
    |**State bit ON** |has recipient, packet has 4 bytes of **IP** recipient| has recipient, packet has 4 bytes of **IP** recipient|

>2-3 Bit describe additional data length, if there's one

 | 2-3 Bit state | Description |
 |:--:|:---:|
 |00| there's no additional data|
|01| data < 256b|
|10| data < 64Kbytes|
|11| data <= 4 Gbytes|

   |     | 4  | 5  | 6 | 7|
   |:--:|:--:|
   |**State bit OFF** | Packet has no command| Has no sequence| Content is not packed| Can receive response in bzip| 
 |**State bit ON** | Has command | Has sequence| Packed by bzip | Cannot receive response in bzip

### Flags 2 description 
>0-6 Bit for CRC

   |     | 7|
   |:--:|:--:|
   |**State bit OFF** | End of flags| 
 |**State bit ON** | Has flags after *Flag 2*| 



Installation
-------------

The simpliest way is to download package and run `composer install` within root Proto folder.


Usage
-------------

This is basic, fully working example of Proto
if you will use or play with it just feel free ask me for ProtoHandshake.