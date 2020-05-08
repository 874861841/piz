<?php

go(function () {
    Co::sleep(1); // 只新增了一行代码
    echo "hello go1 \n";
});

echo "hello main \n";

go(function () {
    echo "hello go2 \n";
});
?>