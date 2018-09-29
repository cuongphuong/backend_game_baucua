<?php
define('HOST_NAME', "http://192.168.43.102");
define('PORT', "8091");
$null = null;

require_once "ChatHandler.php";
require_once "Game.php";
require_once './Database/User.php';
$chatHandler = new ChatHandler();
$user = new User();

$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socketResource, 0, PORT);
socket_listen($socketResource);

$clientSocketArray = array($socketResource);
$clientNameUser = array(); /* {resource => username} */$countClient = 0; // số client kết nối hiện tại
$color = array('#FF99FF', '#66FFFF', '#CC3333', '#9900CC', '#00BB00', '#666666', '#00BB00');
$colorSelected = 0;
$isPlay = false;
$isDatCuoc = false;
$isTa = false;
$chuPhong;
$chuPhongDisConnect = null;
$timeDau;
$timeCuoi;
$timeHT = 0;
$game;

while (true) {
    $newSocketArray = $clientSocketArray;
    socket_select($newSocketArray, $null, $null, 0, 10);

    if (count($clientNameUser) < 6) {
        if (in_array($socketResource, $newSocketArray)) {
            $newSocket = socket_accept($socketResource);
            $clientSocketArray[] = $newSocket;

            $header = socket_read($newSocket, 1024);
            $chatHandler->doHandshake($header, $newSocket, HOST_NAME, PORT);

            socket_getpeername($newSocket, $client_ip_address);
            $connectionACK = $chatHandler->newConnectionACK($client_ip_address);

            $chatHandler->send($connectionACK);

            $newSocketIndex = array_search($socketResource, $newSocketArray);
            unset($newSocketArray[0]);
        } //client connection
    }

    foreach ($newSocketArray as $newSocketArrayResource) {
        while (socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {
            $socketMessage = $chatHandler->unseal($socketData);
            $messageObj = json_decode($socketMessage);
            // get id resource
            $id = substr((string) $newSocketArrayResource, 13, strlen((string) $newSocketArrayResource) - 13);
            if (isset($messageObj->mess_type)) {
                switch ($messageObj->mess_type) {
                    case 'set_name_resource':
                        if (searchName($clientNameUser, $messageObj->name) == true) {
                            // add id_resource = name to $clientNameArray
                            $clientNameUser[$id]['name'] = $messageObj->name;
                            $clientNameUser[$id]['color'] = $color[$colorSelected];
                            echo "Người chơi " . $messageObj->name . " đã kết nối \n";

                            $colorSelected++;
                            if ($colorSelected == 6) {
                                $colorSelected = 0;
                            } //lặp tròn để không trùng màu khi chọn
                        } else {
                            $newSocketIndex = array_search(
                                $newSocketArrayResource,
                                $clientSocketArray
                            );
                            unset($clientSocketArray[$newSocketIndex]);
                        }
                        break;
                    case 'send_message':
                        $chat_box_message = $chatHandler->createChatBoxMessage(
                            $messageObj->chat_user,
                            $messageObj->chat_message
                        );
                        $chatHandler->send($chat_box_message);
                        break;

                    case 'start_game':
                        if (count($clientNameUser) > 1 && $isPlay == false && $clientNameUser[$id]['name'] == $chuPhong) {
                            if ((new User())->getCreditByUsername($chuPhong) >= 1000) {
                                $isPlay = true;
                                $game = new Game(1000);

                                $chat_box_message = $chatHandler->createSystemMessage('begin_game', 'Bắt đầu game');
                                $chatHandler->send($chat_box_message);

                                $isDatCuoc = true;
                                $chat_box_message = $chatHandler->createSystemMessage('cuoc', 'Bắt đầu cược');
                                $chatHandler->send($chat_box_message);

                                $timeDau = time();
                                $timeCuoi = $timeDau + 10;
                            } else {
                                $chat_box_message = $chatHandler->createSystemMessage('notcredit', 'Không đủ tiền');
                                $chatHandler->send($chat_box_message);
                            }
                        } else {
                            $chat_box_message = $chatHandler->createSystemMessage('waiting_user', 'Vui lòng chờ người chơi khác');
                            $chatHandler->send($chat_box_message);
                        }
                        break;

                    case 'dat_cuoc':
                        //kiểm tra nếu không là chủ phòng thì được đặt
                        if ($clientNameUser[$id]['name'] != $chuPhong) {
                            if ($isPlay == true && $isDatCuoc == true) {
                                //Kiểm tra nếu ván chơi đang diển ra và trong thời gian đặt cược
                                if (checkCredit($clientNameUser[$id]['name'], $user->getCreditByUsername($clientNameUser[$id]['name']), $game->cuoc, 1000) == true) {
                                    if (isset($game->cuoc[$messageObj->name][$messageObj->quanco])) {
                                        $game->cuoc[$messageObj->name][$messageObj->quanco] = $game->cuoc[$messageObj->name][$messageObj->quanco] + 1;
                                    } else {
                                        $game->cuoc[$messageObj->name][$messageObj->quanco] = 1;
                                    }

                                    $objCuoc = $chatHandler->createObjCuoc($game->cuoc, $clientNameUser);
                                    $chatHandler->send($objCuoc);
                                }
                            }
                        }
                        break;
                }
            } //client send message
            break 2;
        }

        $socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
        if ($socketData === false) {
            socket_getpeername($newSocketArrayResource, $client_ip_address);
            $connectionACK = $chatHandler->connectionDisconnectACK($client_ip_address);
            $chatHandler->send($connectionACK);
            $newSocketIndex = array_search(
                $newSocketArrayResource,
                $clientSocketArray
            );
            unset($clientSocketArray[$newSocketIndex]);
            echo "Người chơi " . $clientNameUser[substr((string) $newSocketArrayResource, 13, strlen((string) $newSocketArrayResource) - 13)]['name'] . " đã ngắt kết nối \n";
            if ($isPlay == true) {
                $chuPhongDisConnect = $clientNameUser[substr((string) $newSocketArrayResource, 13, strlen((string) $newSocketArrayResource) - 13)]['name'];
            }
            unset($clientNameUser[substr((string) $newSocketArrayResource, 13, strlen((string) $newSocketArrayResource) - 13)]); //unset name resource
        } //user disconnection
    }

    if ($countClient != count($clientNameUser)) {
        $countClient = count($clientNameUser); //update số client hiện tại
        $tmp = current($clientNameUser);
        $chuPhong = $tmp['name'];
        // gửi tin về client
        $chat_box_message = $chatHandler->createObjMessage($user->getLstAvatar($clientNameUser, $chuPhong), 'updateClient');
        $chatHandler->send($chat_box_message);
    }

    if (count($clientNameUser) <= 0 && $isPlay == true) {
        $isPlay = false;
        $isDatCuoc = false;
        $isTa = false;
    }

    // Bắt đầu một ván chơi
    if ($isPlay == true) {

        if (time() - $timeHT >= 0.5) {
            $chat_box_message = $chatHandler->createTimeMessage(persentTime($timeDau, $timeCuoi, time()));
            $chatHandler->send($chat_box_message);
        }

        $timeHT = time();
        // đặt cược
        if ($isDatCuoc == true) {
            if (time() >= $timeCuoi) {
                $isDatCuoc = false;
                $isTa = true;
            }
            if ($isTa == true) {
                $chat_box_message = $chatHandler->createSystemMessage('ta', 'Bắt đầu tả');
                $chatHandler->send($chat_box_message);

                $timeDau = time();
                $timeCuoi = $timeDau + 2;
            }
        }

        // tả
        if ($isTa == true) {
            if (time() >= $timeCuoi) {
                $isTa = false;
                $xx = $game->ranDomXuXac();
                //send xúc xắc về client hiển thị
                $chat_box_message = $chatHandler->createObjMessage($xx, 'xucxac');
                $chatHandler->send($chat_box_message);

                $kq = $game->createObjKQGame($chuPhong, $user->getLstAvatar($clientNameUser, $chuPhong), $game->xuLyThangThua($chuPhongDisConnect, $chuPhong, $xx), $chuPhongDisConnect);
                //send kết quả thắng thua cho client hiển thị
                if ($kq != null) {
                    $chat_box_message = $chatHandler->createObjMessage($kq, 'updateClient');
                    $chatHandler->send($chat_box_message);
                } else {
                    $chat_box_message = $chatHandler->createSystemMessage('error', 'Đã xảy ra lổi game đã bị hủy');
                    $chatHandler->send($chat_box_message);
                }
                $chuPhongDisConnect = null;
                $isPlay = false;
            }
        }
    }
}
socket_close($socketResource);

function persentTime($fistTime, $lasTime, $now)
{
    $totalTime = $lasTime - $fistTime;
    $timePassed = $now - $fistTime;

    $persent = ($timePassed / $totalTime) * 100;
    return $persent;
}

function checkCredit($username, $credit, $cuoc, $tienCuoc)
{
    $tong = 0;
    foreach ($cuoc as $subUsername => $objCuocByUsername) {
        // echo $subUsername . " - " . $username . "\n";
        if ($subUsername == $username) {
            foreach ($objCuocByUsername as $item) {
                $tong += $item;
            }
            break;
            echo $tong;
        }
    }
    if (($tong * $tienCuoc) < $credit && $tong <= 5) {
        return true;
    }
    return false;
}

function searchName($clientN, $name)
{
    foreach ($clientN as $id => $obj) {
        if ($obj['name'] == $name) {
            return false;
        }

    }
    return true;
}
