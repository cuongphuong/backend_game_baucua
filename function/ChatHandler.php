<?php
class ChatHandler
{
    public function send($message)
    {
        global $clientSocketArray;
        $messageLength = strlen($message);
        foreach ($clientSocketArray as $clientSocket) {
            @socket_write($clientSocket, $message, $messageLength);
        }
        return true;
    }

    public function unseal($socketData)
    {
        $length = ord($socketData[1]) & 127;
        if ($length == 126) {
            $masks = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } elseif ($length == 127) {
            $masks = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $masks = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }
        $socketData = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $socketData .= $data[$i] ^ $masks[$i % 4];
        }
        return $socketData;
    }

    public function seal($socketData)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($socketData);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack('CCNN', $b1, 127, $length);
        }

        return $header . $socketData;
    }

    public function doHandshake($received_header, $client_socket_resource, $host_name, $port)
    {
        $headers = array();
        $lines = preg_split("/\r\n/", $received_header);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host_name\r\n" .
            "WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        socket_write($client_socket_resource, $buffer, strlen($buffer));
    }

    public function newConnectionACK($client_ip_address)
    {
        $message = 'New client ' . $client_ip_address . ' joined';
        $messageArray = array('message' => $message, 'message_type' => 'connect');
        $ACK = $this->seal(json_encode($messageArray));
        return $ACK;
    }

    public function connectionDisconnectACK($client_ip_address)
    {
        $message = 'Client ' . $client_ip_address . ' disconnected';
        $messageArray = array('message' => $message, 'message_type' => 'dis_connect');
        $ACK = $this->seal(json_encode($messageArray));
        return $ACK;
    }

    public function createChatBoxMessage($chat_user, $chat_box_message)
    {
        $messageArray = array('message' => $chat_box_message, 'chat_user' => $chat_user, 'message_type' => 'chat_message');
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }

    public function createSystemMessage($command, $message)
    {
        $messageArray = array('command' => $command, 'message' => $message, 'message_type' => 'system_game');
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }

    public function array_search_color($name, $clientNameUser)
    {
        foreach ($clientNameUser as $item) {
            if ($item['name'] == $name) {
                return $item['color'];
            }

		}
		return null;
    }

    public function createObjCuoc($obj, $clientName)
    {
        $objNai = array();
        $objBau = array();
        $objGa = array();
        $objCa = array();
        $objCua = array();
        $objTom = array();
        foreach ($obj as $key => $value) { // name => obj{}
            //lặp trên thằng user
            $tenUser = $key;
            $color = $this->array_search_color($tenUser, $clientName);
            foreach ($value as $subKey => $subValue) {
                //lặp trên từng ô đặt
                if ($subKey == 'nai') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objNai, $push);
                }

                if ($subKey == 'bau') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objBau, $push);
                }

                if ($subKey == 'ga') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objGa, $push);
                }

                if ($subKey == 'ca') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objCa, $push);
                }

                if ($subKey == 'cua') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objCua, $push);
                }

                if ($subKey == 'tom') {
                    $push = array(
                        'color' => $color,
                        'soluong' => $subValue,
                    );
                    array_push($objTom, $push);
                }
            }
        }

        $res = array('nai' => $objNai, 'bau' => $objBau, 'ga' => $objGa, 'ca' => $objCa, 'cua' => $objCua, 'tom' => $objTom);

        $messageArray = array('message_type' => 'updatecuoc', 'data' => $res);
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }

    public function createObjMessage($obj, $type_message)
    {
        $messageArray = array('message_type' => $type_message, 'data' => $obj);
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }

    public function createTimeMessage($time)
    {
        $messageArray = array('message_type' => 'persent_time', 'data' => $time);
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }

}
