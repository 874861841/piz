<?php
/*
 * 文件命名方式为contrller 目录的子目录加上helper
 */
function lalalaapi($value='')
{
	echo $value;
}

//二维数组多字段排序排序
//参考http://www.taotaoit.com/article/details/788.html
//用法$arr = sortArrByManyField($array1, 'id', SORT_ASC, 'name', SORT_ASC, 'age', SORT_DESC);
function sortArrByManyField() {
    $args = func_get_args(); // 获取函数的参数的数组
// func_get_args()获取函数参数列表的数组。
// 该函数可以配合 func_get_arg() 和 func_num_args() 一起使用，从而使得用户自定义函数可以接受自定义个数的参数列表。
 
    if (empty($args)) {
        return null;
    } 
    $arr = array_shift($args); // array_shift() 函数删除数组中第一个元素，并返回被删除元素的值
    if (!is_array($arr)) {
        throw new Exception("第一个参数不为数组");
    } 
    foreach($args as $key => $field) {
        if (is_string($field)) {
            $temp = array();
            foreach($arr as $index => $val) {
                $temp[$index] = $val[$field];
            } 
            $args[$key] = $temp;
        } 
    } 
    $args[] = &$arr; //引用值,为啥要传引用呢，因为使用了call_user_func_array，array_multisort函数成了回调函数
    call_user_func_array('array_multisort', $args); //把第一个参数作为回调函数（callback）调用，把参数数组作（param_arr）为回调函数的的参数传入。返回回调函数的结果。如果出错的话就返回FALSE,百度下这个函数即有解释

    return array_pop($args); // array_pop()功能是数组的最后一个元素出栈，返回值是数组的最后一个元素
}

//分页,默认从第一页开始,每页100条数据，limitStart 和 limitCount和mysql的分页一样
function paginate(array $array,int $limitStart = 0,int $limitCount = 100){
	return  array_slice($array,$limitStart,$limitCount);
}

