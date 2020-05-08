<?php
return [
    //Server::onStart
    'start'     => [
        [\app\hook\FD::class,'start'],
    ],
    //Server::onOpen
    'open'      => [
        [\app\hook\FD::class,'open'],
    ],
    //Server::onClose
    'close'     => [
        [\app\hook\FD::class,'close'],
    ],
];