<?php
require_once './Database/User.php';

class Game
{
    public $cuoc = array();
    public $tienCuoc = 0;
    // public $user;

    public function Game($tienCuoc)
    {
        $this->tienCuoc = $tienCuoc;
        // $user = new User();
    }

    private function chuyen($xxInt)
    {
        $xxStr;
        if ($xxInt == 1) {
            $xxStr = 'nai';
        } else if ($xxInt == 2) {
            $xxStr = 'bau';
        } else if ($xxInt == 3) {
            $xxStr = 'ga';
        } else if ($xxInt == 4) {
            $xxStr = 'ca';
        } else if ($xxInt == 5) {
            $xxStr = 'cua';
        } else if ($xxInt == 6) {
            $xxStr = 'tom';
        }

        return $xxStr;
    }

    public function ranDomXuXac()
    {
        $xucxac1 = rand(1, 6);
        $xucxac2 = rand(1, 6);
        $xucxac3 = rand(1, 6);
        return array('xx1' => $this->chuyen($xucxac1), 'xx2' => $this->chuyen($xucxac2), 'xx3' => $this->chuyen($xucxac3));
    }

    public function xuLyKQGame($chuPhongDisCOnnect, $chuPhongHT, $xx)
    {
        $kq = array();
        $chuPhong = $chuPhongHT;

        if ($chuPhongDisCOnnect != null) {
            $chuPhong = $chuPhongDisCOnnect;
        }

        foreach ($this->cuoc as $key => $item) {
            // lặp trên từng người chơi
            $username = $key;
            $tienThang = 0;
            $tienThua = 0;
            if ($username != $chuPhong) {
                foreach ($item as $tenCCDat => $soLuongDat) {
                    $isCong = false;
                    foreach ($xx as $xucxac) {
                        if ($tenCCDat == $xucxac) {
                            $tienThang = $tienThang + $this->tienCuoc * $soLuongDat;
                            $isCong = true;
                        }
                    }
                    if ($isCong == false) {
                        $tienThua = $tienThua - $this->tienCuoc * $soLuongDat;
                    }
                }
                $kq[$username]['win'] = abs($tienThang);
                $kq[$username]['lost'] = abs($tienThua);
            }
        }
        $chuPhongWin = 0;
        $chuPhongLost = 0;
        $kqChuPhong = array();
        foreach ($kq as $item) {
            $chuPhongWin = $chuPhongWin + abs($item['lost']);
            $chuPhongLost += abs($item['win']);
        }
        if ($chuPhongDisCOnnect != null) {
            // $kq[$chuPhong]['win'] = 0;
            $kqChuPhong[$chuPhong]['win'] = 0;
        } else {
            // $kq[$chuPhong]['win'] = $chuPhongWin;
            $kqChuPhong[$chuPhong]['win'] = $chuPhongWin;
        }
        // $kq[$chuPhong]['lost'] = $chuPhongLost;
        $kqChuPhong[$chuPhong]['lost'] = $chuPhongLost;
        $kq = $kqChuPhong + $kq;
        return $kq;
    }

    public function tinhTongTienThua($kq, $nameChuPhong)
    {
        $tongTien = 0;
        foreach ($kq as $tenNguoiChoi => $arrKQ) {
            if ($tenNguoiChoi != $nameChuPhong) {
                $tongTien = $tongTien + $arrKQ['lost'] - $arrKQ['win'];
            }
        }
        // nếu kết quả âm (chủ phòng thua thì return trị tuyệt đối số tiền thua, ngược lại thua 0 (tức chủ phòng hòa hoặt ăn) (return 0));
        if ($tongTien < 0) {
            return abs($tongTien);
        }
        return 0;
    }

    public function xuLyKQGame2($chuPhong, $tienChuPhong, $objKq)
    {
        //xu ly khi tien chu phong khoong du tra cho client
        $sumWin = 0;
        $arrayKqRes = array();
        foreach ($objKq as $tenNguoiChoi => $arrKqChoi) {
            if ($arrKqChoi['win'] - $arrKqChoi['lost'] > 0) {
                $sumWin += $arrKqChoi['win'] - $arrKqChoi['lost'];
            }
        }
        //
        $arrayKqRes[$chuPhong]['win'] = 0;
        $arrayKqRes[$chuPhong]['lost'] = $tienChuPhong;
        foreach ($objKq as $tenNguoiChoi => $arrKqChoi) {
            if ($tenNguoiChoi != $chuPhong) {
                if ($arrKqChoi['win'] - $arrKqChoi['lost'] > 0) {
                    // thang
                    $arrayKqRes[$tenNguoiChoi]['win'] = (($arrKqChoi['win'] - $arrKqChoi['lost']) / $sumWin) * $tienChuPhong;
                    $arrayKqRes[$tenNguoiChoi]['lost'] = 0;
                } else if ($arrKqChoi['win'] - $arrKqChoi['lost'] < 0) {
                    //thua
                    $arrayKqRes[$tenNguoiChoi]['win'] = 0;
                    $arrayKqRes[$tenNguoiChoi]['lost'] = (abs($arrKqChoi['win'] - $arrKqChoi['lost']) / $sumWin) * $tienChuPhong;
                } else {
                    //hoa
                    $arrayKqRes[$tenNguoiChoi]['win'] = 0;
                    $arrayKqRes[$tenNguoiChoi]['lost'] = 0;
                }
            }
        }
        return $arrayKqRes;
    }

    public function xuLyThangThua($chuPhongDisCOnnect, $chuPhongHT, $xx)
    {
        $kqGame = $this->xuLyKQGame($chuPhongDisCOnnect, $chuPhongHT, $xx);
        $chuPhong = $chuPhongHT;
        if ($chuPhongDisCOnnect != null) {
            $chuPhong = $chuPhongDisCOnnect;
        }
        $user = new User();
        $creditChuPhong = $user->getCreditByUsername($chuPhong);
        $tienThuaChuPhong = $this->tinhTongTienThua($kqGame, $chuPhong);

        if ($creditChuPhong < $tienThuaChuPhong) {
            $kqGame = $this->xuLyKQGame2($chuPhong, $creditChuPhong, $kqGame);
        }
        $user = new User();
        foreach ($kqGame as $tenNguoiChoi => $value) {
            if ($value['win'] - $value['lost'] > 0) {
                $this->congTien($tenNguoiChoi, $value['win'] - $value['lost']);
            } else if ($value['win'] - $value['lost'] < 0) {
                $this->truTien($tenNguoiChoi, abs($value['win'] - $value['lost']));
            }
        }

        //update tiền vào database
        return $kqGame;
    }

    public function createObjKQGame($chuPhong, $objUserAndCredit, $kqThangThua, $chuPhongDisCOnnect)
    {
        $res = array();
        $user = new User();
        foreach ($objUserAndCredit['lstavt'] as $item) {
            $kqUser = 0;

            if (isset($kqThangThua[$item['name']])) {
                $kqUser = ($kqThangThua[$item['name']]['win']) - ($kqThangThua[$item['name']]['lost']);
            }
            $newObj = array(
                'avatar' => $item['avatar'],
                'credit' => (float)$user->getCreditByUsername($item['name']),
                'name' => $item['name'],
                'color' => $item['color'],
                'kqGame' => $kqUser,
            );
            if ($chuPhongDisCOnnect != null && $newObj['name'] == $chuPhongDisCOnnect) {
            } else {
                array_push($res, $newObj);
            }
        }
        // echo json_encode(array('isGame' => true, 'lstavt' => $res)) . "\n";
        return array('isGame' => true, 'chuphong' => $chuPhong, 'lstavt' => $res);
    }

    public function congTien($username, $soTien)
    {
        $user = new User();
        $user->updateCredit($username, $soTien, 1);
    }

    public function truTien($username, $soTien)
    {
        $user = new User();
        $user->updateCredit($username, $soTien, 0);
    }

}
