<?php

$check = preg_match('/^[a-zA-Z1-9]+$/', '1234');
if ($check) {
    echo 'Chuỗi khớp';
} else {
    echo 'Chuỗi không khớp';
}
